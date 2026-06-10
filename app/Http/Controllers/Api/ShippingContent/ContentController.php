<?php

namespace App\Http\Controllers\Api\ShippingContent;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Support\Permissions\ContentPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'content.page');
        $this->authorizePermission($request, 'content.view');

        $contents = Content::query()
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $contents->map(fn (Content $content): array => $this->filterVisibleColumns($request, $content))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'content.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:content,name'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $content = Content::query()->create($data);

        return response()->json([
            'message' => 'Content created successfully.',
            'data' => $this->filterVisibleColumns($request, $content),
        ], 201);
    }

    public function show(Request $request, Content $content): JsonResponse
    {
        $this->authorizePermission($request, 'content.page');
        $this->authorizePermission($request, 'content.view');

        return response()->json($this->filterVisibleColumns($request, $content));
    }

    public function update(Request $request, Content $content): JsonResponse
    {
        $this->authorizePermission($request, 'content.update');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('content', 'name')->ignore($content->id),
            ],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $content->update($data);

        return response()->json([
            'message' => 'Content updated successfully.',
            'data' => $this->filterVisibleColumns($request, $content),
        ]);
    }

    public function destroy(Request $request, Content $content): JsonResponse
    {
        $this->authorizePermission($request, 'content.delete');

        $content->delete();

        return response()->json([
            'message' => 'Content deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    /**
     * @param array<int, string> $columns
     */
    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = ContentPermissionMap::CONTENT_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission for column [{$column}]: {$permission}");
            }
        }
    }

    private function filterVisibleColumns(Request $request, Content $content): array
    {
        $raw = $content->toArray();
        $result = [];

        foreach (ContentPermissionMap::CONTENT_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $raw)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $raw[$column];
            }
        }

        return $result;
    }
}
