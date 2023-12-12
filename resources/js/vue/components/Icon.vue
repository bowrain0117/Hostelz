<template>
  <span :style="styles"><svg :width="this.width" :height="this.height"><use :xlink:href="href"></use></svg></span>
</template>

<script>

export default {
  name: 'Icon',
  data() {
    return {
      href: `#${this.icon}`
    }
  },
  props: {
    width: {
      type: Number,
      default:50,
    },
    height: {
      type: Number,
      default:50,
    },
    icon: {
      type: String,
      default: 'logo-min'
    }
  },
  computed: {
    styles() {
      return {
        width: `${this.width}px`,
        height: `${this.height}px`
      }
    },
  },
}
</script>

<style scoped>

</style>