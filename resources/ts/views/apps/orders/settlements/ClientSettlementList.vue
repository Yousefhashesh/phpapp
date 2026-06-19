<script setup lang="ts">
import { useApi } from "@/composables/useApi";
import { useFlashHighlight } from '@/composables/useFlashHighlight'
import { useUserRole } from '@/composables/useUserRole';
import { createUrl } from "@core/composable/createUrl";
import { avatarText } from "@core/utils/formatters";
import { useRoute } from 'vue-router';

const route = useRoute()
const router = useRouter()
const { flash, isFlashing } = useFlashHighlight()
const { isClientUser } = useUserRole()

const searchQuery = ref("");
const selectedIds = ref<number[]>([]);
const processingAction = ref(false);
const selectedStatus = ref<string | null>(null);
const selectedApprovalStatus = ref<string | null>(null);
const selectedClient = ref<number | null>(route.query.client_user_id ? Number(route.query.client_user_id) : null);

//    Headers
const headers = [
  { title: "#ID", key: "id" },
  { title: "Client", key: "client" },
  { title: "Date", key: "settlement_date" },
  { title: "Orders", key: "number_of_orders" },
  { title: "Total", key: "total_amount" },
  { title: "Fees", key: "fees" },
  { title: "COD", key: "net_amount" },
  { title: "Status", key: "status" },
  { title: "Approval", key: "approval_status" },
  { title: "Actions", key: "actions", sortable: false, width: "180px" },
];

const { can } = useAbility();

const STORAGE_KEY = "client-settlements-visible-columns";

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  client: "client-settlement.column.client_user_id.view",
  settlement_date: "client-settlement.column.settlement_date.view",
  number_of_orders: "client-settlement.column.number_of_orders.view",
  total_amount: "client-settlement.column.total_amount.view",
  fees: "client-settlement.column.fees.view",
  net_amount: "client-settlement.column.net_amount.view",
  status: "client-settlement.column.status.view",
};

const visibleHeaderKeys = ref(
  JSON.parse(
    localStorage.getItem(STORAGE_KEY) ||
      JSON.stringify(headers.map((h) => h.key)),
  ),
);

const activeHeaders = computed(() => {
  return headers.filter((h) => {
    // 1. Check user manual visibility
    if (!visibleHeaderKeys.value.includes(h.key)) return false;

    // 2. Check permission
    const perm = columnPermissions[h.key];
    if (perm && !can(perm as any, "all" as any)) return false;

    return true;
  });
});

const filteredHeadersForMenu = computed(() => {
  return headers.filter((h) => {
    const perm = columnPermissions[h.key];
    return !perm || can(perm as any, "all" as any);
  });
});

watch(visibleHeaderKeys, (newVal) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
});

// Debounced Search Logic
const searchQueryDebounced = ref("");
let searchTimer: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    searchQueryDebounced.value = val || "";
  }, 400);
});

//    Fetching Settlements
const {
  data: settlementsData,
  execute: fetchSettlements,
  isFetching,
} = useApi<any>(
  createUrl("/client-settlements", {
    query: {
      status: selectedStatus,
      approval_status: selectedApprovalStatus,
      client_user_id: selectedClient,
      search: searchQueryDebounced,
    },
  }),
).get().json();

const settlements = computed(() => settlementsData.value?.data || []);
const totalSettlements = computed(() => settlementsData.value?.meta?.total || 0);

// For simplicity, we keep VDataTable (client side) for now but use the fetched data.
// If you want full server side pagination, you would need v-model:page etc.

// Totals for visible settlements
const visibleTotals = computed(() => {
  const list = settlements.value;
  let total_amount = 0;
  let fees = 0;
  let cod = 0;
  let net_amount = 0;
  let number_of_orders = 0;
  for (const s of list) {
    total_amount += Number(s.total_amount) || 0;
    fees += Number(s.fees) || 0;
    cod += Number(s.cod_amount) || 0;
    net_amount += Number(s.net_amount) || 0;
    number_of_orders += Number(s.number_of_orders) || 0;
  }
  return { total_amount, fees, cod, net_amount, number_of_orders };
});

const statusColors: any = {
  PENDING: "warning",
  COMPLETED: "success",
  CANCELLED: "error",
};
const approvalColors: any = {
  PENDING: "warning",
  APPROVED: "success",
  REJECTED: "error",
};

//    Actions
const selectedSettlement = ref<any>(null);
const isCreateDialogVisible = ref(false);
const isApprovalDialogVisible = ref(false);
const approvalAction = ref<"approve" | "reject">("approve");
const approvalNote = ref("");

const isEditDialogVisible = ref(false);
const editSettlementData = ref({
  id: null as number | null,
  settlement_date: "",
  total_amount: 0,
  fees: 0,
  net_amount: 0,
});
const isSavingEdit = ref(false);
const editErrorMessages = ref<string[]>([]);

const openEditDialog = (item: any) => {
  editSettlementData.value = {
    id: item.id,
    settlement_date: item.settlement_date ? new Date(item.settlement_date).toISOString().substr(0, 10) : "",
    total_amount: Number(item.total_amount) || 0,
    fees: Number(item.fees) || 0,
    net_amount: Number(item.net_amount) || 0,
  };
  editErrorMessages.value = [];
  isEditDialogVisible.value = true;
};

const patchSettlement = (id: number, patch: Record<string, any>) => {
  const list = settlementsData.value?.data
  if (!list)
    return

  const index = list.findIndex((s: any) => s.id === id)
  if (index === -1)
    return

  list[index] = { ...list[index], ...patch }
  flash(`settlement-${id}`)
}

const submitEdit = async () => {
  if (!editSettlementData.value.id) return;
  
  isSavingEdit.value = true;
  editErrorMessages.value = [];
  
  try {
    const { data, error } = await useApi(`/client-settlements/${editSettlementData.value.id}`)
      .patch({
        settlement_date: editSettlementData.value.settlement_date,
        total_amount: editSettlementData.value.total_amount,
        fees: editSettlementData.value.fees,
        net_amount: editSettlementData.value.net_amount,
      })
      .json();
      
    if (error.value) {
      if ((error.value as any).data?.errors) {
        editErrorMessages.value = Object.values((error.value as any).data.errors).flat() as string[];
      } else {
        editErrorMessages.value = [(error.value as any).message || 'Failed to update settlement'];
      }
    } else {
      isEditDialogVisible.value = false;
      patchSettlement(editSettlementData.value.id, data.value?.data || editSettlementData.value);
    }
  } catch (e: any) {
    editErrorMessages.value = ['An error occurred while saving.'];
  } finally {
    isSavingEdit.value = false;
  }
};

const viewDetails = (id: number) => {
  router.push({ name: 'apps-orders-client-settlements-id', params: { id } })
};

const openApprovalDialog = (id: number, action: "approve" | "reject") => {
  selectedSettlement.value = settlements.value.find((c: any) => c.id === id);
  approvalAction.value = action;
  approvalNote.value = "";
  isApprovalDialogVisible.value = true;
};

const submitApproval = async () => {
  if (!selectedSettlement.value) return;

  processingAction.value = true;
  const settlementId = selectedSettlement.value.id;
  const url = `/client-settlements/${settlementId}/${approvalAction.value}`;
  const { data, error } = await useApi(url)
    .patch({ approval_note: approvalNote.value })
    .json();

  if (!error.value) {
    isApprovalDialogVisible.value = false;
    patchSettlement(settlementId, {
      approval_status: approvalAction.value === 'approve' ? 'APPROVED' : 'REJECTED',
      ...(data.value?.data || {}),
    });
  }
  processingAction.value = false;
};

const updateStatus = async (id: number, status: string) => {
  processingAction.value = true;
  const { data, error } = await useApi(`/client-settlements/${id}`)
    .patch({ status })
    .json();

  if (!error.value) {
    patchSettlement(id, { status, ...(data.value?.data || {}) });
  }
  processingAction.value = false;
};

const clients = ref<any[]>([]);
const searchClients = async (val: string = "") => {
  if (isClientUser.value)
    return

  try {
    const { data: res } = await useApi<any>(
      createUrl("/clients", {
        query: { q: val, eligible_for: "settlement", per_page: 20 },
      }),
    )
      .get()
      .json();
    const data = res.value?.data || res.value || [];
    clients.value = data.map((c: any) => ({
      id: c.user_id,
      user_id: c.user_id,
      name: c.user?.name || "Unknown",
    }));
  } catch (e) {
    console.error(e);
  }
};

watch(
  [searchQueryDebounced, selectedStatus, selectedApprovalStatus, selectedClient],
  () => {
    fetchSettlements();
  },
);

onMounted(() => {
  if (!isClientUser.value)
    searchClients();
});

import ClientSettlementModal from "./ClientSettlementModal.vue";

const onSettlementCreated = (settlement?: any) => {
  if (settlement && settlementsData.value?.data) {
    settlementsData.value.data.unshift(settlement)
    flash(`settlement-${settlement.id}`)
  } else {
    fetchSettlements()
  }
}

const bulkUpdateStatus = async (status: string) => {
  if (selectedIds.value.length === 0) return;

  processingAction.value = true;
  const ids = selectedIds.value.map((i: any) => i.id || i);
  const { error } = await useApi("/client-settlements/bulk-status")
    .patch({
      ids,
      status: status,
    })
    .json();

  if (!error.value) {
    ids.forEach((id: number) => patchSettlement(id, { status }));
    selectedIds.value = [];
  }
  processingAction.value = false;
};

const printInvoice = (id: number) => {
  window.open(`/apps/orders/print/${id}?type=settlement`, "_blank");
};
//    Export

const exportSettlements = async () => {
  const params: any = {};

  if (selectedIds.value.length > 0) {
    params.ids = selectedIds.value.map((i: any) => i.id || i).join(",");
  } else {
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedApprovalStatus.value)
      params.approval_status = selectedApprovalStatus.value;
    if (selectedClient.value) params.client_user_id = selectedClient.value;
  }

  const queryParams = new URLSearchParams(params).toString();
  const token = useCookie("accessToken").value || "";
  window.open(
    `/api/client-settlements/export?${queryParams}&token=${token}`,
    "_blank",
  );
};
</script>

<template>
  <section>
    <!-- Totals Cards -->
    <VRow class="mb-4">
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="primary" icon="tabler-currency-dollar" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.total_amount.toLocaleString() }}</div>
              <div class="text-xs text-disabled">إجمالي المبلغ</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="error" icon="tabler-receipt-tax" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.fees.toLocaleString() }}</div>
              <div class="text-xs text-disabled">إجمالي الرسوم</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="success" icon="tabler-cash" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.cod.toLocaleString() }}</div>
              <div class="text-xs text-disabled">إجمالي COD</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="warning" icon="tabler-wallet" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.net_amount.toLocaleString() }}</div>
              <div class="text-xs text-disabled">إجمالي الصافي</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="6" md="2">
        <VCard elevation="2" class="stats-card">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="info" icon="tabler-list-numbers" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.number_of_orders }}</div>
              <div class="text-xs text-disabled">عدد الطلبات</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <ClientSettlementModal
      v-model:is-dialog-visible="isCreateDialogVisible"
      @settlement-created="onSettlementCreated"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <div class="d-flex align-center flex-wrap gap-4 flex-grow-1">
          <div style="inline-size: 15rem">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Client..."
              prepend-inner-icon="tabler-search"
              clearable
            />
          </div>
          <AppSelect
            v-if="!isClientUser"
            v-model="selectedClient"
            placeholder="Select Client"
            :items="clients"
            item-title="name"
            item-value="user_id"
            clearable
            style="inline-size: 15rem"
            @update:search="searchClients"
          />
          <AppSelect
            v-model="selectedStatus"
            placeholder="Status"
            :items="['PENDING', 'COMPLETED', 'CANCELLED']"
            clearable
            style="inline-size: 10rem"
          />
          <AppSelect
            v-model="selectedApprovalStatus"
            placeholder="Approval"
            :items="['PENDING', 'APPROVED', 'REJECTED']"
            clearable
            style="inline-size: 10rem"
          />
        </div>
        <div class="d-flex gap-2">
          <!-- Bulk Status Update -->
          <VBtn
            v-if="
              selectedIds.length > 0 &&
              can('client-settlement.update' as any, 'all' as any)
            "
            color="secondary"
            variant="tonal"
            prepend-icon="tabler-settings"
          >
            Bulk Status
            <VMenu activator="parent">
              <VList>
                <VListItem @click="bulkUpdateStatus('PENDING')">
                  <VListItemTitle>Mark as Pending</VListItemTitle>
                </VListItem>
                <VListItem @click="bulkUpdateStatus('COMPLETED')">
                  <VListItemTitle>Mark as Completed</VListItemTitle>
                </VListItem>
                <VListItem @click="bulkUpdateStatus('CANCELLED')">
                  <VListItemTitle>Mark as Cancelled</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>

          <VBtn
            v-if="can('client-settlement.export' as any, 'all' as any)"
            variant="tonal"
            color="primary"
            :prepend-icon="
              selectedIds.length > 0
                ? 'tabler-file-spreadsheet'
                : 'tabler-file-download'
            "
            :loading="processingAction"
            @click="exportSettlements"
          >
            {{
              selectedIds.length > 0
                ? `Export Selected (${selectedIds.length})`
                : "Export All"
            }}
          </VBtn>
          <VBtn
            v-if="can('client-settlement.create' as any, 'all' as any)"
            color="primary"
            prepend-icon="tabler-plus"
            @click="isCreateDialogVisible = true"
          >
            Create Settlement
          </VBtn>

          <!--    Column Visibility Toggle -->
          <VMenu :close-on-content-click="false">
            <template #activator="{ props }">
              <VBtn icon variant="tonal" color="secondary" v-bind="props">
                <VIcon icon="tabler-layout-columns" />
              </VBtn>
            </template>
            <VList class="pa-2">
              <VListItem
                v-for="h in filteredHeadersForMenu"
                :key="h.key"
                density="compact"
              >
                <VCheckbox
                  v-model="visibleHeaderKeys"
                  :value="h.key"
                  :label="h.title"
                  hide-details
                  density="compact"
                />
              </VListItem>
            </VList>
          </VMenu>
        </div>
      </VCardText>

      <VDivider />

      <VDataTable
        v-model="selectedIds"
        item-value="id"
        return-object
        show-select
        :items="settlements"
        :headers="activeHeaders"
        :loading="isFetching"
        class="text-no-wrap"
        :row-props="({ item }: { item: any }) => ({
          class: isFlashing(`settlement-${item.id}`) ? 'row-flash' : '',
        })"
        loading-text="تحميل البيانات..."
      >
        <!-- ID -->
        <template #item.id="{ item }: { item: any }">
          <span class="text-primary font-weight-bold">#{{ item.id }}</span>
        </template>

        <!-- Client -->
        <template #item.client="{ item }: { item: any }">
          <div class="d-flex align-center gap-x-2">
            <VAvatar size="28" color="success" variant="tonal">
              <span class="text-xs">{{
                avatarText(item.client?.name || "C")
              }}</span>
            </VAvatar>
            <span class="text-sm text-high-emphasis">{{
              item.client?.name || "-"
            }}</span>
          </div>
        </template>

        <!-- Date -->
        <template #item.settlement_date="{ item }: { item: any }">
          <span class="text-sm">{{
            new Date(item.settlement_date).toLocaleDateString()
          }}</span>
        </template>

        <!-- Money -->
        <template #item.total_amount="{ item }: { item: any }">
          <span class="text-base font-weight-medium"
            >{{ item.total_amount }} EGP</span
          >
        </template>

        <template #item.fees="{ item }: { item: any }">
          <span class="text-base text-error">{{ item.fees }} EGP</span>
        </template>

        <template #item.net_amount="{ item }: { item: any }">
          <span class="text-base font-weight-bold text-success"
            >{{ item.net_amount }} EGP</span
          >
        </template>

        <!-- Status -->
        <template #item.status="{ item }: { item: any }">
          <VChip
            size="x-small"
            :color="statusColors[item.status]"
            variant="tonal"
            class="text-capitalize"
            style="font-size: 11px !important;"
          >
            {{ item.status }}
          </VChip>
        </template>

        <!-- Approval -->
        <template #item.approval_status="{ item }: { item: any }">
          <VChip
            size="x-small"
            :color="approvalColors[item.approval_status]"
            variant="tonal"
            class="text-capitalize"
            style="font-size: 11px !important;"
          >
            {{ item.approval_status }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex gap-1 align-center">
            <IconBtn
              v-if="can('client-settlement.view' as any, 'all' as any)"
              size="small"
              @click="viewDetails(item.id)"
            >
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent">View Details</VTooltip>
            </IconBtn>

            <!-- Edit Details -->
            <IconBtn
              v-if="can('client-settlement.update' as any, 'all' as any)"
              size="small"
              color="secondary"
              @click="openEditDialog(item)"
            >
              <VIcon icon="tabler-edit" />
              <VTooltip activator="parent">Edit Settlement</VTooltip>
            </IconBtn>

            <!-- Invoice -->
            <VBtn
              v-if="can('client-settlement.view' as any, 'all' as any)"
              size="small"
              icon
              color="primary"
              variant="tonal"
              class="rounded"
              @click="printInvoice(item.id)"
            >
              <VIcon icon="tabler-file-invoice" size="20" />
              <VTooltip activator="parent">Print Invoice</VTooltip>
            </VBtn>

            <template v-if="item.approval_status === 'PENDING'">
              <IconBtn
                v-if="can('client-settlement.approve' as any, 'all' as any)"
                size="small"
                color="success"
                @click="openApprovalDialog(item.id, 'approve')"
              >
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approve</VTooltip>
              </IconBtn>
              <IconBtn
                v-if="can('client-settlement.reject' as any, 'all' as any)"
                size="small"
                color="error"
                @click="openApprovalDialog(item.id, 'reject')"
              >
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Reject</VTooltip>
              </IconBtn>
            </template>

            <!-- Settlement Status Change -->
            <VBtn
              v-if="can('client-settlement.update' as any, 'all' as any)"
              size="x-small"
              color="secondary"
              variant="tonal"
              :loading="processingAction"
            >
              Status
              <VMenu activator="parent">
                <VList density="compact">
                  <VListItem @click="updateStatus(item.id, 'PENDING')">
                    <VListItemTitle>Pending</VListItemTitle>
                  </VListItem>
                  <VListItem @click="updateStatus(item.id, 'COMPLETED')">
                    <VListItemTitle>Complete</VListItemTitle>
                  </VListItem>
                  <VListItem @click="updateStatus(item.id, 'CANCELLED')">
                    <VListItemTitle>Cancel</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </VBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Approval Dialog -->
    <VDialog v-model="isApprovalDialogVisible" max-width="500">
      <VCard
        :title="
          approvalAction === 'approve'
            ? 'Approve Settlement'
            : 'Reject Settlement'
        "
      >
        <VCardText>
          <p>
            Are you sure you want to {{ approvalAction }} settlement #{{
              selectedSettlement?.id
            }}?
          </p>
          <AppTextarea
            v-model="approvalNote"
            label="Note (Optional)"
            placeholder="Add a comment..."
            rows="3"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isApprovalDialogVisible = false"
            >Cancel</VBtn
          >
          <VBtn
            :color="approvalAction === 'approve' ? 'success' : 'error'"
            :loading="processingAction"
            @click="submitApproval"
          >
            {{ approvalAction === "approve" ? "Approve" : "Reject" }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Edit Dialog -->
    <VDialog v-model="isEditDialogVisible" max-width="600">
      <VCard title="تعديل تفاصيل التسوية">
        <VCardText>
          <VAlert
            v-if="editErrorMessages.length"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            <div v-for="err in editErrorMessages" :key="err">{{ err }}</div>
          </VAlert>
          
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="editSettlementData.settlement_date"
                label="تاريخ التسوية"
                type="date"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="editSettlementData.total_amount"
                label="إجمالي المبلغ"
                type="number"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="editSettlementData.fees"
                label="الرسوم والخصومات"
                type="number"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="editSettlementData.net_amount"
                label="صافي المبلغ (مستحقات العميل)"
                type="number"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isEditDialogVisible = false"
          >
            إلغاء
          </VBtn>
          <VBtn
            color="primary"
            :loading="isSavingEdit"
            @click="submitEdit"
          >
            حفظ التعديلات
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<style scoped>
:deep(.row-flash) {
  animation: row-flash 2s ease-out;
}

@keyframes row-flash {
  0% {
    box-shadow: inset 0 0 0 2px rgb(var(--v-theme-primary));
  }

  100% {
    box-shadow: inset 0 0 0 0 transparent;
  }
}
</style>
