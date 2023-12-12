<script setup>
import {ref} from "vue";

const props = defineProps({
  show: Boolean,
})

const toggleRef = ref(null)

</script>

<template>
  <div class="toggle-wrap" ref="toggleRef"
       :style="{ maxHeight: show ? toggleRef.children[0].offsetHeight + 'px' : '0' }"
  >
    <slot></slot>
  </div>
</template>

<style scoped>
.toggle-wrap {
  transition-property: all;
  transition-duration: .3s;
  transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
  overflow-y: hidden;
}
</style>