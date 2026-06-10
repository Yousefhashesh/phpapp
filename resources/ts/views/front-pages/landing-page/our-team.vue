<script setup lang="ts">
const teamData = [
  { name: 'أحمد محمود', position: 'مدير العمليات', color: 'primary', icon: 'tabler-user' },
  { name: 'سارة حسن', position: 'خدمة العملاء', color: 'info', icon: 'tabler-user' },
  { name: 'محمد علي', position: 'مسؤول اللوجستيات', color: 'success', icon: 'tabler-user' },
  { name: 'ليلى إبراهيم', position: 'المحاسبة', color: 'warning', icon: 'tabler-user' },
]
</script>

<template>
  <VContainer id="team">
    <div class="our-team">
      <div class="headers d-flex justify-center flex-column align-center">
        <VChip
          label
          color="primary"
          class="mb-4"
          size="small"
        >
          فريق العمل
        </VChip>
        <h4 class="d-flex align-center text-h4 mb-1 flex-wrap justify-center text-center">
          فريقنا المتخصص وراء 
          <div class="position-relative ms-2">
            <div class="section-title">
              نجاحك
            </div>
          </div>
        </h4>
        <p class="text-body-1 mb-0 text-center">
          نحن نوفر لك أفضل الخبرات في مجال اللوجستيات لضمان أداء مثالي.
        </p>
      </div>

      <VRow class="mt-12">
        <VCol
          v-for="(data, index) in teamData"
          :key="index"
          cols="12"
          md="3"
          sm="6"
        >
          <VCard
            flat
            border
            class="position-relative overflow-visible"
          >
            <VCardText class="pa-0">
              <div
                :style="{ backgroundColor: `rgb(var(--v-theme-${data.color}))` }"
                class="team-image-container d-flex align-center justify-center rounded-t"
              >
                  <VAvatar
                    size="150"
                    variant="tonal"
                    color="white"
                    class="team-image shadow-sm"
                  >
                    <VIcon :icon="data.icon" size="80" />
                  </VAvatar>
              </div>
              <div class="pa-4 text-center">
                <h5 class="text-h5">
                  {{ data.name }}
                </h5>
                <p class="text-body-1 text-disabled mb-0">
                  {{ data.position }}
                </p>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>
  </VContainer>
</template>

<style lang="scss" scoped>
.team-image-container {
  min-block-size: 200px;
}

.team-image {
  margin-block-start: 1rem;
}

.our-team {
  margin-block: 5.25rem;
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  line-height: 36px;
}
</style>
