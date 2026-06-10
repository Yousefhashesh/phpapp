# K-YEY Logistics Platform 🚀

A comprehensive delivery management system with a Laravel 12 API, Vue 3 Dashboard, and Flutter Mobile Application.

---

## 🛠 Tech Stack

- **Backend**: Laravel 12, Sanctum, Spatie Permissions.
- **Frontend**: Vue 3 (Composition API), TypeScript, Vuetify, Casl RBAC.
- **Mobile**: Flutter, Cubit State Management, Dio.
- **Database**: MySQL.

---

## 📖 Documentation

We have a complete professional documentation system built with **VitePress**. This documentation covers:

- **Project Overview**: Terminology, Business Goals, and Stakeholders.
- **Developer Handover**: Backend architecture, Frontend logic, and Mobile app engine.
- **Mobile Handover**: In-depth guide to Flutter + Cubit.
- **Maintenance**: Troubleshooting, Deployment, and Onboarding Checklists.

### How to Run the Documentation

To view the technical handover and user guides locally:

1. **Install dependencies** (if not already done):

    ```bash
    npm install
    # OR
    pnpm install
    ```

2. **Start the documentation server**:
    npm run docs:dev: لتشغيل التوثيق في وضع التطوير (معاينة حية).
    npm run docs:build: لبناء نسخة الإنتاج من التوثيق.
    npm run docs:preview: لمعاينة النسخة المبنية.

    ```bash
    npm run docs:dev
    ```

3. **Open in your browser**:
    The documentation will be available at [http://localhost:5173](http://localhost:5173).

---

## 🚀 Getting Started (Main Project)

### 1. Backend Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=SyncPermissionsSeeder
```

### 2. Frontend Setup

```bash
npm install
npm run dev
```

### 3. Mobile Setup

```bash
cd mobile_app_directory
flutter pub get
flutter run
```

---

## 🛡 Security & RBAC

The system uses a strict **Role-Based Access Control (RBAC)** system:

- **Backend**: Enforced via `authorizePermission` in controllers and `forUserRole()` scopes in Eloquent queries.
- **Frontend**: Managed via **Casl** rules fetched from the `/acl` endpoint.
- **Super Admin**: Bypasses all gate checks for complete system visibility.

---

## 🤝 Project Handover

For a successful project handover, please refer to the **Maintenance & Handover** section in the [VitePress Docs](docs/maintenance/checklist.md). It contains a detailed checklist for new developers.

---

**Developed with ❤️ for K-YEY Logistics.**

---
## ABDO RADWAN EDITS

-  التعديل الأول كان في `app/Http/Controllers/Api/Users/UserController.php`، حيث عدّلنا دالة `destroy()` لتقوم بحذف السجل نهائيًا (`forceDelete`) بعد إزالة أي صفوف مرتبطة في جداول `shipper` و `client`.
- بعد ذلك، عدّلنا `app/Support/Permissions/OrdersPermissionMap.php` ليتضمن الحقول `has_return` و `has_return_at` ضمن أعمدة العرض الصالحة في API الطلبات.
- تم تكرار نفس التعديل في النسخة الفرعية `start/app/Support/Permissions/OrdersPermissionMap.php` لكي تكون متسقة مع بيئة التطوير/النسخة الاحتياطية.
- هذا التعديل صحح مشكلة عدم ظهور علامة المرتجع في واجهة الطلبات رغم أن الحقل كان يُحدث في قاعدة البيانات.
 