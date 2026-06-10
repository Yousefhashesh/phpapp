<script setup lang="ts">
import FinancialOrdersDetailTable from '@/views/apps/orders/components/FinancialOrdersDetailTable.vue'
import { useApi } from "@/composables/useApi";
import { useUserRole } from '@/composables/useUserRole';
import { createUrl } from "@core/composable/createUrl";
import { avatarText } from "@core/utils/formatters";
import { useRoute } from 'vue-router';

const route = useRoute()
const { isClientUser } = useUserRole()

const searchQuery = ref("");
const selectedIds = ref<number[]>([]);
const processingAction = ref(false);
const selectedStatus = ref<string | null>(null);
const selectedApprovalStatus = ref<string | null>(null);
const selectedClient = ref<number | null>(route.query.client_user_id ? Number(route.query.client_user_id) : null);

// 👉 Headers
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

// 👉 Fetching Settlements
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

// 👉 Actions
const isDetailsDialogVisible = ref(false);
const selectedSettlement = ref<any>(null);
const isCreateDialogVisible = ref(false);
const isApprovalDialogVisible = ref(false);
const approvalAction = ref<"approve" | "reject">("approve");
const approvalNote = ref("");

const viewDetails = async (id: number) => {
  const { data } = await useApi<any>(`/client-settlements/${id}`).get().json();
  if (data.value) {
    selectedSettlement.value = data.value;
    isDetailsDialogVisible.value = true;
  }
};

const settlementFinancialSummary = computed(() => {
  const orders = selectedSettlement.value?.orders ?? [];
  let totalAmount = 0;
  let totalFees = 0;
  let totalCod = 0;

  for (const order of orders) {
    totalAmount += Number(order.total_amount) || 0;
    totalFees += Number(order.shipping_fee) || 0;
    totalCod += Number(order.cod_amount) || 0;
  }

  return { totalAmount, totalFees, totalCod };
});

const formatSummaryMoney = (value: number) =>
  `EGP ${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const openApprovalDialog = (id: number, action: "approve" | "reject") => {
  selectedSettlement.value = settlements.value.find((c: any) => c.id === id);
  approvalAction.value = action;
  approvalNote.value = "";
  isApprovalDialogVisible.value = true;
};

const submitApproval = async () => {
  if (!selectedSettlement.value) return;

  processingAction.value = true;
  const url = `/client-settlements/${selectedSettlement.value.id}/${approvalAction.value}`;
  const { error } = await useApi(url)
    .patch({ approval_note: approvalNote.value })
    .json();

  if (!error.value) {
    isApprovalDialogVisible.value = false;
    fetchSettlements();
  }
  processingAction.value = false;
};

const updateStatus = async (id: number, status: string) => {
  processingAction.value = true;
  const { error } = await useApi(`/client-settlements/${id}`)
    .patch({ status })
    .json();

  if (!error.value) {
    fetchSettlements();
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
const removeOrderFromSettlement = async (orderId: number) => {
  if (!selectedSettlement.value) return;
  
  processingAction.value = true;
  const { data, error } = await useApi(`/client-settlements/${selectedSettlement.value.id}/orders/${orderId}`)
    .delete()
    .json();

  if (!error.value) {
    if (data.value?.deleted) {
      isDetailsDialogVisible.value = false;
      selectedSettlement.value = null;
    } else {
      selectedSettlement.value = data.value?.data || data.value;
    }
    fetchSettlements();
  }
  processingAction.value = false;
};

const bulkUpdateStatus = async (status: string) => {
  if (selectedIds.value.length === 0) return;

  processingAction.value = true;
  const { error } = await useApi("/client-settlements/bulk-status")
    .patch({
      ids: selectedIds.value.map((i: any) => i.id || i),
      status: status,
    })
    .json();

  if (!error.value) {
    selectedIds.value = [];
    fetchSettlements();
  }
  processingAction.value = false;
};

const printInvoice = (id: number) => {
  window.open(`/apps/orders/print/${id}?type=settlement`, "_blank");
};
// 👉 Export

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
      @settlement-created="fetchSettlements"
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

          <!-- 👉 Column Visibility Toggle -->
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

    <!-- Details Dialog -->
    <VDialog
      v-model="isDetailsDialogVisible"
      max-width="1200"
      scrollable
    >
      <VCard :title="`Settlement Details - #${selectedSettlement?.id}`">
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              md="4"
            >
              <div class="text-subtitle-2 mb-1">
                Total Amount
              </div>
              <div class="text-body-1 font-weight-bold">
                {{ formatSummaryMoney(settlementFinancialSummary.totalAmount) }}
              </div>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <div class="text-subtitle-2 mb-1">
                Total Fees
              </div>
              <div class="text-body-1 font-weight-bold text-error">
                {{ formatSummaryMoney(settlementFinancialSummary.totalFees) }}
              </div>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <div class="text-subtitle-2 mb-1">
                COD Amount
              </div>
              <div class="text-body-1 font-weight-bold text-success">
                {{ formatSummaryMoney(settlementFinancialSummary.totalCod) }}
              </div>
            </VCol>
          </VRow>

          <VDivider class="my-4" />

          <div class="text-h6 mb-2">
            Orders
          </div>
          <FinancialOrdersDetailTable
            :orders="selectedSettlement?.orders || []"
            :show-actions="can('client-settlement.update' as any, 'all' as any)"
            :processing-action="processingAction"
            @remove="removeOrderFromSettlement"
          />
        </VCardText>
        <VCardActions>
          <VBtn
            v-if="
              selectedSettlement?.approval_status === 'PENDING' &&
              can('client-settlement.approve' as any, 'all' as any)
            "
            color="success"
            variant="tonal"
            prepend-icon="tabler-check"
            @click="openApprovalDialog(selectedSettlement.id, 'approve')"
          >
            Approve
          </VBtn>
          <VBtn
            v-if="
              selectedSettlement?.approval_status === 'PENDING' &&
              can('client-settlement.reject' as any, 'all' as any)
            "
            color="error"
            variant="tonal"
            prepend-icon="tabler-x"
            @click="openApprovalDialog(selectedSettlement.id, 'reject')"
          >
            Reject
          </VBtn>
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isDetailsDialogVisible = false"
            >Close</VBtn
          >
        </VCardActions>
      </VCard>
    </VDialog>

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
  </section>
</template>
