<script setup lang="ts">
// Contact Us for Shipya
</script>

<template>
  <div id="contact-us">
    <VContainer>
      <div class="contact-us-section pa-12">
        <div class="headers d-flex justify-center flex-column align-center text-center">
          <VChip
            label
            color="primary"
            class="mb-4"
            size="small"
          >
            تواصل معنا
          </VChip>
          <h4 class="d-flex align-center text-h4 mb-1 flex-wrap justify-center text-center">
            نحن هنا 
            <div class="position-relative ms-2">
              <div class="section-title">
                لمساعدتك
              </div>
            </div>
            في أي وقت
          </h4>
          <p class="text-body-1 mb-8">
            هل لديك أي استفسار؟ فريقنا متاح دائماً للرد على أسئلتك.
          </p>
        </div>

        <VRow class="justify-center">
          <VCol
            cols="12"
            md="5"
          >
            <VCard border flat class="h-100 pa-10">
              <div class="d-flex align-center gap-4 mb-6">
                <VAvatar color="primary" variant="tonal" rounded size="50">
                  <VIcon icon="tabler-mail" />
                </VAvatar>
                <div>
                  <div class="text-sm">راسلنا عبر البريد الإلكتروني</div>
                  <div class="text-h6">support@shipya.com</div>
                </div>
              </div>
              
              <div class="d-flex align-center gap-4 mb-6">
                <VAvatar color="primary" variant="tonal" rounded size="50">
                  <VIcon icon="tabler-phone" />
                </VAvatar>
                <div>
                  <div class="text-sm">اتصل بنا</div>
                  <div class="text-h6">010XXXXXXXX</div>
                </div>
              </div>

              <div class="d-flex align-center gap-4 mb-6">
                <VAvatar color="primary" variant="tonal" rounded size="50">
                  <VIcon icon="tabler-map-pin" />
                </VAvatar>
                <div>
                  <div class="text-sm">موقعنا</div>
                  <div class="text-h6">القاهرة، مصر</div>
                </div>
              </div> or find us on social media
            </VCard>
          </VCol>
          <VCol
            cols="12"
            md="7"
          >
            <VCard border flat class="pa-10">
              <VForm @submit.prevent>
                <VRow>
                  <VCol cols="12" md="6">
                    <VTextField label="الاسم بالكامل" placeholder="ادخل اسمك" />
                  </VCol>
                  <VCol cols="12" md="6">
                    <VTextField label="البريد الإلكتروني" placeholder="البريد الإلكتروني" />
                  </VCol>
                  <VCol cols="12">
                    <VTextarea label="رسالتك" placeholder="كيف يمكننا مساعدتك؟" rows="4" />
                  </VCol>
                  <VCol cols="12">
                    <VBtn block size="large">إرسال الرسالة</VBtn>
                  </VCol>
                </VRow>
              </VForm>
            </VCard>
          </VCol>
        </VRow>
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
.contact-us-section {
  margin-block: 5.25rem;
}

.section-title {
  font-size: 24px;
  font-weight: 800;
  line-height: 36px;
}
</style>
