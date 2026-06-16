<script setup lang="ts">
import FinancialOrdersDetailTable from '@/views/apps/orders/components/FinancialOrdersDetailTable.vue'
import { useApi } from "@/composables/useApi";
import { createUrl } from "@core/composable/createUrl";
import { avatarText } from "@core/utils/formatters";
import { useRoute } from 'vue-router';

const route = useRoute()

const searchQuery = ref("");
const selectedStatus = ref<string | null>(null);
const selectedApprovalStatus = ref<string | null>(null);
const selectedShipper = ref<number | null>(route.query.shipper_user_id ? Number(route.query.shipper_user_id) : null);

const { can } = useAbility();

const STORAGE_KEY = "shipper-collections-visible-columns";
const selectedIds = ref<number[]>([]);
const processingAction = ref(false);

// Define permission mapping for columns
const columnPermissions: Record<string, string> = {
  shipper: "shipper-collection.column.shipper_user_id.view",
  collection_date: "shipper-collection.column.collection_date.view",
  number_of_orders: "shipper-collection.column.number_of_orders.view",
  total_amount: "shipper-collection.column.total_amount.view",
  shipper_fees: "shipper-collection.column.shipper_fees.view",
  net_amount: "shipper-collection.column.net_amount.view",
  status: "shipper-collection.column.status.view",
};

//    Headers
const headers = [
  { title: "#ID", key: "id" },
  { title: "Shipper", key: "shipper" },
  { title: "Date", key: "collection_date" },
  { title: "Orders", key: "number_of_orders" },
  { title: "Total", key: "total_amount" },
  { title: "Fees", key: "fees" },
  { title: "Shipper Fees", key: "shipper_fees" },
  { title: "Net", key: "net_amount" },
  { title: "Status", key: "status" },
  { title: "Approval", key: "approval_status" },
  { title: "Actions", key: "actions", sortable: false, width: "180px" },
];

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

const searchQueryDebounced = ref("");
let searchTimer: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    searchQueryDebounced.value = val || "";
  }, 400);
});

//    Fetching Collections
const {
  data: collectionsData,
  execute: fetchCollections,
  isFetching,
} = useApi<any>(
  createUrl("/shipper-collections", {
    query: {
      status: selectedStatus,
      approval_status: selectedApprovalStatus,
      shipper_user_id: selectedShipper,
      search: searchQueryDebounced,
    },
  }),
).get().json();

const collections = computed(() => collectionsData.value?.data || []);
const totalCollections = computed(() => collectionsData.value?.meta?.total || 0);

// Totals for visible collections
const visibleTotals = computed(() => {
  const list = collections.value;
  let total_amount = 0;
  let net_amount = 0;
  let shipper_fees = 0;
  let number_of_orders = 0;
  for (const c of list) {
    total_amount += Number(c.total_amount) || 0;
    net_amount += Number(c.net_amount) || 0;
    shipper_fees += Number(c.shipper_fees) || 0;
    number_of_orders += Number(c.number_of_orders) || 0;
  }
  return { total_amount, net_amount, shipper_fees, number_of_orders };
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
const isDetailsDialogVisible = ref(false);
const selectedCollection = ref<any>(null);
const isCreateDialogVisible = ref(false);
const isApprovalDialogVisible = ref(false);
const approvalAction = ref<"approve" | "reject">("approve");
const approvalNote = ref("");

const viewDetails = async (id: number) => {
  const { data } = await useApi<any>(`/shipper-collections/${id}`).get().json();
  if (data.value) {
    selectedCollection.value = data.value;
    isDetailsDialogVisible.value = true;
  }
};

const collectionFinancialSummary = computed(() => {
  const orders = selectedCollection.value?.orders ?? [];
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
  selectedCollection.value = collections.value.find((c: any) => c.id === id);
  approvalAction.value = action;
  approvalNote.value = "";
  isApprovalDialogVisible.value = true;
};

const submitApproval = async () => {
  if (!selectedCollection.value) return;

  processingAction.value = true;
  const url = `/shipper-collections/${selectedCollection.value.id}/${approvalAction.value}`;
  const { error } = await useApi(url)
    .patch({ approval_note: approvalNote.value })
    .json();

  if (!error.value) {
    isApprovalDialogVisible.value = false;
    fetchCollections();
  }
  processingAction.value = false;
};

const updateStatus = async (id: number, status: string) => {
  processingAction.value = true;
  const { error } = await useApi(`/shipper-collections/${id}`)
    .patch({ status })
    .json();

  if (!error.value) {
    fetchCollections();
  }
  processingAction.value = false;
};

const shippers = ref<any[]>([]);
const searchShippers = async (val: string = "") => {
  try {
    const { data: res } = await useApi<any>(
      createUrl("/shippers", { query: { q: val, per_page: 20 } }),
    )
      .get()
      .json();
    const data = res.value?.data || res.value || [];
    shippers.value = data.map((s: any) => ({
      id: s.user_id,
      user_id: s.user_id,
      name: s.user?.name || "Unknown",
    }));
  } catch (e) {
    console.error(e);
  }
};

watch(
  [searchQueryDebounced, selectedStatus, selectedApprovalStatus, selectedShipper],
  () => {
    fetchCollections();
  },
);

onMounted(() => {
  searchShippers();
});

import ShipperCollectionModal from "./ShipperCollectionModal.vue";
const removeOrderFromCollection = async (orderId: number) => {
  if (!selectedCollection.value) return;
  
  processingAction.value = true;
  const { data, error } = await useApi(`/shipper-collections/${selectedCollection.value.id}/orders/${orderId}`)
    .delete()
    .json();

  if (!error.value) {
    if (data.value?.deleted) {
      isDetailsDialogVisible.value = false;
      selectedCollection.value = null;
    } else {
      selectedCollection.value = data.value?.data || data.value;
    }
    fetchCollections();
  }
  processingAction.value = false;
};

const bulkUpdateStatus = async (status: string) => {
  if (selectedIds.value.length === 0) return;

  processingAction.value = true;
  const { error } = await useApi("/shipper-collections/bulk-status")
    .patch({
      ids: selectedIds.value.map((i: any) => i.id || i),
      status: status,
    })
    .json();

  if (!error.value) {
    selectedIds.value = [];
    fetchCollections();
  }
  processingAction.value = false;
};

const printInvoice = (id: number) => {
  window.open(`/apps/orders/print/${id}?type=collection`, "_blank");
};

//    Export

const exportCollections = async () => {
  const params: any = {};

  if (selectedIds.value.length > 0) {
    params.ids = selectedIds.value.map((i: any) => i.id || i).join(",");
  } else {
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedApprovalStatus.value)
      params.approval_status = selectedApprovalStatus.value;
    if (selectedShipper.value) params.shipper_user_id = selectedShipper.value;
  }

  const queryParams = new URLSearchParams(params).toString();
  const token = useCookie("accessToken").value || "";
  window.open(
    `/api/shipper-collections/export?${queryParams}&token=${token}`,
    "_blank",
  );
};
</script>

<template>
  <section>
    <!-- Totals Cards -->
    <VRow class="mb-4">
      <VCol cols="6" md="3">
        <VCard elevation="2">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="primary" icon="tabler-currency-dollar" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.total_amount.toLocaleString() }}</div>
              <div class="text-xs text-disabled">إجمالي التحصيل</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="6" md="3">
        <VCard elevation="2">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="success" icon="tabler-cash" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.net_amount.toLocaleString() }}</div>
              <div class="text-xs text-disabled">إجمالي الصافي</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="6" md="3">
        <VCard elevation="2">
          <VCardText class="d-flex align-center gap-3 pa-3">
            <VAvatar variant="tonal" color="error" icon="tabler-receipt-tax" size="38" />
            <div>
              <div class="text-h6 font-weight-bold">{{ visibleTotals.shipper_fees.toLocaleString() }}</div>
              <div class="text-xs text-disabled">عمولات المناديب</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="6" md="3">
        <VCard elevation="2">
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

    <ShipperCollectionModal
      v-model:is-dialog-visible="isCreateDialogVisible"
      @collection-created="fetchCollections"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <div class="d-flex align-center flex-wrap gap-4 flex-grow-1">
          <div style="inline-size: 15rem">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Shipper..."
              prepend-inner-icon="tabler-search"
              clearable
            />
          </div>
          <AppSelect
            v-model="selectedShipper"
            placeholder="Select Shipper"
            :items="shippers"
            item-title="name"
            item-value="user_id"
            clearable
            style="inline-size: 15rem"
            @update:search="searchShippers"
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
              can('shipper-collection.update' as any, 'all' as any)
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
            v-if="can('shipper-collection.export' as any, 'all' as any)"
            variant="tonal"
            color="primary"
            :prepend-icon="
              selectedIds.length > 0
                ? 'tabler-file-spreadsheet'
                : 'tabler-file-download'
            "
            :loading="processingAction"
            @click="exportCollections"
          >
            {{
              selectedIds.length > 0
                ? `Export Selected (${selectedIds.length})`
                : "Export All"
            }}
          </VBtn>
          <VBtn
            v-if="can('shipper-collection.create' as any, 'all' as any)"
            color="primary"
            prepend-icon="tabler-plus"
            @click="isCreateDialogVisible = true"
          >
            Create Collection
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
        :items="collections"
        :headers="activeHeaders"
        :loading="isFetching"
        class="text-no-wrap"
        loading-text="تحميل البيانات..."
      >
        <!-- ID -->
        <template #item.id="{ item }: { item: any }">
          <span class="text-primary font-weight-bold">#{{ item.id }}</span>
        </template>

        <!-- Shipper -->
        <template #item.shipper="{ item }: { item: any }">
          <div class="d-flex align-center gap-x-2">
            <VAvatar size="28" color="info" variant="tonal">
              <span class="text-xs">{{
                avatarText(item.shipper?.name || "S")
              }}</span>
            </VAvatar>
            <span class="text-sm text-high-emphasis">{{
              item.shipper?.name || "-"
            }}</span>
          </div>
        </template>

        <!-- Date -->
        <template #item.collection_date="{ item }: { item: any }">
          <span class="text-sm">{{
            new Date(item.collection_date).toLocaleDateString()
          }}</span>
        </template>

        <!-- Money -->
        <template #item.total_amount="{ item }: { item: any }">
          <span class="text-base font-weight-medium"
            >{{ item.total_amount }} EGP</span
          >
        </template>
        <template #item.fees="{ item }: { item: any }">
          <span class="text-error">{{ item.fees }} EGP</span>
        </template>
        <template #item.shipper_fees="{ item }: { item: any }">
          <span class="text-base font-weight-medium"
            >{{ item.shipper_fees }} EGP</span
          >
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
            size="small"
            :color="approvalColors[item.approval_status]"
            variant="tonal"
            class="text-capitalize"
          >
            {{ item.approval_status }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }: { item: any }">
          <div class="d-flex gap-1 align-center">
            <!-- View Details -->
            <IconBtn
              v-if="can('shipper-collection.view' as any, 'all' as any)"
              size="small"
              @click="viewDetails(item.id)"
            >
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent">View Details</VTooltip>
            </IconBtn>

            <!-- Print Invoice -->
            <VBtn
              v-if="can('shipper-collection.view' as any, 'all' as any)"
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

            <!-- Approval Status (Approve/Reject) -->
            <template
              v-if="
                item.approval_status &&
                item.approval_status.toString().toUpperCase() === 'PENDING'
              "
            >
              <IconBtn
                v-if="can('shipper-collection.approve' as any, 'all' as any)"
                size="small"
                color="success"
                @click="openApprovalDialog(item.id, 'approve')"
              >
                <VIcon icon="tabler-check" />
                <VTooltip activator="parent">Approve (Approval)</VTooltip>
              </IconBtn>
              <IconBtn
                v-if="can('shipper-collection.reject' as any, 'all' as any)"
                size="small"
                color="error"
                @click="openApprovalDialog(item.id, 'reject')"
              >
                <VIcon icon="tabler-x" />
                <VTooltip activator="parent">Reject (Approval)</VTooltip>
              </IconBtn>
            </template>

            <!-- Collection Status Change -->
            <VBtn
              v-if="can('shipper-collection.update' as any, 'all' as any)"
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
      <VCard :title="`Collection Details - #${selectedCollection?.id}`">
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
                {{ formatSummaryMoney(collectionFinancialSummary.totalAmount) }}
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
                {{ formatSummaryMoney(collectionFinancialSummary.totalFees) }}
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
                {{ formatSummaryMoney(collectionFinancialSummary.totalCod) }}
              </div>
            </VCol>
          </VRow>

          <VDivider class="my-4" />

          <div class="text-h6 mb-2">
            Orders
          </div>
          <FinancialOrdersDetailTable
            :orders="selectedCollection?.orders || []"
            :show-actions="can('shipper-collection.update' as any, 'all' as any)"
            :processing-action="processingAction"
            @remove="removeOrderFromCollection"
          />
        </VCardText>
        <VCardActions>
          <VBtn
            v-if="
              selectedCollection?.approval_status &&
              selectedCollection.approval_status.toString().toUpperCase() ===
                'PENDING'
            "
            color="success"
            variant="tonal"
            prepend-icon="tabler-check"
            @click="openApprovalDialog(selectedCollection.id, 'approve')"
          >
            Approve
          </VBtn>
          <VBtn
            v-if="
              selectedCollection?.approval_status &&
              selectedCollection.approval_status.toString().toUpperCase() ===
                'PENDING'
            "
            color="error"
            variant="tonal"
            prepend-icon="tabler-x"
            @click="openApprovalDialog(selectedCollection.id, 'reject')"
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
            ? 'Approve Collection'
            : 'Reject Collection'
        "
      >
        <VCardText>
          <p>
            Are you sure you want to {{ approvalAction }} collection #{{
              selectedCollection?.id
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
