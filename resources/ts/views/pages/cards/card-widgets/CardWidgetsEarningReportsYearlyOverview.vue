<script setup lang="ts">
import { hexToRgb } from '@layouts/utils'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()

interface Report {
  title: string
  icon: string
  categories: string[]
  data: number[]
  highlightIndex?: number
}

interface Props {
  title?: string
  subtitle?: string
  reports: Report[]
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Earning Reports',
  subtitle: 'Yearly Earnings Overview',
})

const currentTab = ref<number>(0)
const refVueApexChart = ref()

const chartConfigs = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  const labelPrimaryColor = `rgba(${hexToRgb(currentTheme.primary)},${variableTheme['dragged-opacity']})`
  const legendColor = `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['high-emphasis-opacity']})`
  const borderColor = `rgba(${hexToRgb(String(variableTheme['border-color']))},${variableTheme['border-opacity']})`
  const labelColor = `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`

  return props.reports.map((report, reportIdx) => {
    const maxVal = Math.max(...report.data) || 10
    const tickAmount = 5

    return {
      title: report.title,
      icon: report.icon,
      chartOptions: {
        chart: {
          parentHeightOffset: 0,
          type: 'bar',
          toolbar: { show: false },
        },
        plotOptions: {
          bar: {
            columnWidth: '35%',
            borderRadius: 6,
            distributed: true,
            dataLabels: { position: 'top' },
          },
        },
        grid: {
          show: false,
          padding: { top: 0, bottom: 0, left: -10, right: -10 },
        },
        colors: report.data.map((_, idx) => 
          idx === report.highlightIndex 
            ? `rgba(${hexToRgb(currentTheme.primary)}, 1)` 
            : labelPrimaryColor
        ),
        dataLabels: {
          enabled: true,
          formatter(val: number) {
            return val >= 1000 ? `${(val / 1000).toFixed(1)}k` : val.toString()
          },
          offsetY: -25,
          style: {
            fontSize: '12px',
            colors: [legendColor],
            fontWeight: '600',
            fontFamily: 'Public Sans',
          },
        },
        legend: { show: false },
        tooltip: { enabled: true },
        xaxis: {
          categories: report.categories,
          axisBorder: { show: true, color: borderColor },
          axisTicks: { show: false },
          labels: {
            style: {
              colors: labelColor,
              fontSize: '11px',
              fontFamily: 'Public Sans',
            },
            rotate: -45,
            rotateAlways: false,
          },
        },
        yaxis: {
          labels: {
            offsetX: -15,
            formatter(val: number) {
              return val >= 1000 ? `${(val / 1000).toFixed(0)}k` : val.toString()
            },
            style: {
              fontSize: '12px',
              colors: labelColor,
              fontFamily: 'Public Sans',
            },
          },
          min: 0,
          max: maxVal * 1.2,
          tickAmount: tickAmount,
        },
        responsive: [
          {
            breakpoint: 1441,
            options: { plotOptions: { bar: { columnWidth: '45%' } } },
          },
          {
            breakpoint: 600,
            options: {
              yaxis: { labels: { show: false } },
              dataLabels: { style: { fontSize: '10px' } },
            },
          },
        ],
      },
      series: [{ data: report.data }],
    }
  })
})

const moreList = [
  { title: 'View More', value: 'View More' },
  { title: 'Delete', value: 'Delete' },
]
</script>

<template>
  <VCard
    :title="props.title"
    :subtitle="props.subtitle"
  >
    <template #append>
      <div class="mt-n4 me-n2">
        <MoreBtn :menu-list="moreList" />
      </div>
    </template>

    <VCardText>
      <VSlideGroup
        v-model="currentTab"
        show-arrows
        mandatory
        class="mb-10"
      >
        <VSlideGroupItem
          v-for="(report, index) in chartConfigs"
          :key="report.title"
          v-slot="{ isSelected, toggle }"
          :value="index"
        >
          <div
            style="block-size: 100px; inline-size: 110px;"
            :style="isSelected ? 'border-color:rgb(var(--v-theme-primary)) !important' : ''"
            :class="isSelected ? 'border' : 'border border-dashed'"
            class="d-flex flex-column justify-center align-center cursor-pointer rounded py-4 px-5 me-4"
            @click="toggle"
          >
            <VAvatar
              rounded
              size="38"
              :color="isSelected ? 'primary' : ''"
              variant="tonal"
              class="mb-2"
            >
              <VIcon
                size="22"
                :icon="report.icon"
              />
            </VAvatar>
            <h6 class="text-base font-weight-medium mb-0">
              {{ report.title }}
            </h6>
          </div>
        </VSlideGroupItem>

        <!-- 👉 slider more -->
        <VSlideGroupItem>
          <div
            style="block-size: 100px; inline-size: 110px;"
            class="d-flex flex-column justify-center align-center rounded border border-dashed py-4 px-5"
          >
            <VAvatar
              rounded
              size="38"
              variant="tonal"
            >
              <VIcon
                size="22"
                icon="tabler-plus"
              />
            </VAvatar>
          </div>
        </VSlideGroupItem>
      </VSlideGroup>

      <VueApexCharts
        ref="refVueApexChart"
        :key="currentTab"
        :options="chartConfigs[Number(currentTab)].chartOptions"
        :series="chartConfigs[Number(currentTab)].series"
        height="230"
        class="mt-3"
      />
    </VCardText>
  </VCard>
</template>
