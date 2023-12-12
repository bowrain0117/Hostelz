<script setup>
import {nextTick, onBeforeMount, onMounted, reactive, ref} from "vue";
import ToggleElement from "../../components/ToggleElement.vue";

const props = defineProps({
  faqs: Object,
})

const items = reactive(
    props.faqs
        .filter(i => i.answer)
        .map(i => ({...i, show: false}))
);

const classObject = reactive({
  active: true,
  'text-danger': false
})

</script>

<template>
  <div id="faqs">
    <div v-for="(faq, key) in items" :key="key"
         class="card border-0 mb-4 pb-2"
    >
      <div id="heading" class="tx-body font-weight-600">
        <a @click.prevent="faq.show = ! faq.show"
           class="accordion-link cl-text py-0"
           href="#"
        >
          {{ faq.question }}
          <i class="fas float-right"
             :class="{'fa-angle-down': !faq.show, 'fa-angle-up': faq.show}"
          ></i>
        </a>
      </div>
      <ToggleElement :show="faq.show" class="mt-2">
        <div class="tx-body cl-text text-content" v-html="faq.answer"></div>
      </ToggleElement>
    </div>
  </div>
</template>

<style scoped>

</style>