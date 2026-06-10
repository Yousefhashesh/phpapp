<script setup lang="ts">
const reviewData = [
  {
    desc: 'من أفضل المنصات التي تعاملت معها، سهولة في الاستخدام ودقة في التحصيل المالي.',
    img: 'tabler-user',
    name: 'محمود جاسم',
    pos: 'صاحب متجر ملابس',
    rating: 5,
  },
  {
    desc: 'الـ API الخاص بهم سهل جداً، ربطت متجري في دقائق وبدأت الشحن فوراً.',
    img: 'tabler-user',
    name: 'سارة هاني',
    pos: 'تاجرة إلكترونية',
    rating: 5,
  },
  {
    desc: 'نظام المرتجعات بالباركود وفر علينا الكثير من الوقت والمجهود يومياً.',
    img: 'tabler-user',
    name: 'محمد زين',
    pos: 'مدير عمليات',
    rating: 4,
  },
]
</script>

<template>
  <div id="customers-review" class="reviews-wrapper py-16">
    <VContainer>
      <div class="headers d-flex justify-center flex-column align-center mb-16">
        <VChip variant="tonal" color="primary" class="mb-4">آراء العملاء</VChip>
        <h2 class="section-headline text-center mb-4">شركاء نجاحنا يتحدثون</h2>
        <p class="section-subline text-center">أكثر من 500 متجر يعتمدون على Shipya يومياً لتنمية أعمالهم.</p>
      </div>

      <VRow>
        <VCol
          v-for="(review, index) in reviewData"
          :key="index"
          cols="12"
          md="4"
          sm="6"
        >
          <div class="glass-review-card pa-8 h-100">
            <div class="d-flex mb-6">
              <VIcon v-for="i in 5" :key="i" icon="tabler-star-filled" size="18" :color="i <= review.rating ? 'warning' : 'grey-lighten-2'" class="me-1" />
            </div>
            
            <p class="review-text mb-8">"{{ review.desc }}"</p>
            
            <div class="d-flex align-center gap-4">
              <VAvatar color="primary" variant="tonal" size="48">
                <VIcon :icon="review.img" size="24" />
              </VAvatar>
              <div>
                <div class="reviewer-name">{{ review.name }}</div>
                <div class="reviewer-pos">{{ review.pos }}</div>
              </div>
            </div>
          </div>
        </VCol>
      </VRow>

      <div class="brands-divider my-16 opacity-20"></div>

      <div class="d-flex justify-center align-center flex-wrap gap-x-16 gap-y-8 opacity-30 brands-strip">
        <VIcon icon="tabler-brand-shopee" size="40" />
        <VIcon icon="tabler-brand-amazon" size="40" />
        <VIcon icon="tabler-brand-delivery" size="40" />
        <VIcon icon="tabler-brand-mailchimp" size="40" />
      </div>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
@import "https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Cairo:wght@400;700;900&display=swap";

.reviews-wrapper {
  background: #f8fafc;
  font-family: Cairo, Outfit, sans-serif;
}

.dark .reviews-wrapper { background: #0f172a; }

.section-headline {
  color: #1e293b;
  font-size: 2.25rem;
  font-weight: 900;
}
.dark .section-headline { color: white; }

.section-subline {
  color: #64748b;
  font-size: 1.1rem;
  max-inline-size: 600px;
}

.glass-review-card {
  border: 1px solid rgba(0, 0, 0, 5%);
  border-radius: 24px;
  background: white;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 2%);
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 20px 40px rgba(0, 0, 0, 5%);
    transform: translateY(-5px);
  }
}

.dark .glass-review-card {
  border: 1px solid rgba(255, 255, 255, 5%);
  background: rgba(30, 41, 59, 40%);
}

.review-text {
  color: #334155;
  font-size: 1.1rem;
  font-style: italic;
  line-height: 1.7;
}

.dark .review-text { color: #cbd5e1; }

.reviewer-name { color: #1e293b; font-size: 1rem; font-weight: 800; }
.dark .reviewer-name { color: white; }
.reviewer-pos { color: #64748b; font-size: 0.85rem; }

.brands-divider {
  background: linear-gradient(to right, transparent, #94a3b8, transparent);
  block-size: 1px;
}

.brands-strip {
  filter: grayscale(1);
}
</style>
