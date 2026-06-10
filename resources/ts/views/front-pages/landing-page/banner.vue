<script setup lang="ts">
// Banner for Shipya
</script>

<template>
  <div
    class="landing-cta landing-cta-gradient"
  >
    <VContainer>
      <div class="d-flex justify-center justify-md-space-between flex-wrap gap-6 gap-x-10 position-relative pt-12 pb-12">
        <div class="align-self-center text-center text-md-start">
          <div class="banner-title text-white mb-1">
            هل أنت جاهز للنمو؟
          </div>
          <h5 class="text-h5 text-white mb-8" style="opacity: 0.85;">
            ابدأ الآن وجرب منصة Shipya مجاناً لمدة 14 يوم واستمتع بكل المزايا.
          </h5>
          <VBtn
            color="white"
            class="text-primary"
            :to="{ name: 'pages-authentication-register-v1' }"
            :size="$vuetify.display.smAndUp ? 'large' : 'default' "
          >
            ابدأ الآن مجاناً
          </VBtn>
        </div>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss">
.landing-cta-gradient {
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  border-radius: 2rem;
  margin-block: 4rem;
}

.banner-title {
  font-size: 34px;
  font-weight: 700;
  line-height: 44px;
}
</style>
