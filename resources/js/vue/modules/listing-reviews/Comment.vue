<template>
  <p class="mb-0">
    <div class="text-break" v-html="searchString.length > 0 ? addHighlights(displayedText) : displayedText"></div>
    <span @click="showFullText = !showFullText"
          class="text-sm cl-link ml-1"
          :class="{ 'd-none': countWords(this.comment) < maxLength }"
    >read {{ readMoreText }}</span>
  </p>
</template>

<script>
import { mapState } from "vuex"

export default {
  name: 'Comment',
  props: ['comment'],
  data() {
    return {
      showFullText: false,
      maxLength: 80,
    }
  },
  computed: {
    ...mapState('listingReviewsModules', [
      'searchString',
    ]),
    displayedText() {
      let comment = this.comment.replace(/(<([^>]+)>)/gi, '').split(/[\r\n]+/gm).join('<br/>')

      if (this.showFullText) {
        return comment
      }

      if (this.countWords(comment) < this.maxLength) {
        return comment
      }
      return comment.split(' ').splice(0, this.maxLength).join(' ') + '...'
    },
    readMoreText() {
      return this.showFullText ? 'less' : 'more'
    },
  },
  methods: {
    addHighlights(str) {
      const searchIndex = str.toLowerCase().indexOf(this.searchString.toLowerCase())
      const searchStringLength = this.searchString.length
      if (searchIndex >= 0) {
        return str.substring(0, searchIndex)
            + `<mark>`
            + str.substring(searchIndex, searchIndex + searchStringLength)
            + `</mark>`
            + str.substring(searchIndex + searchStringLength)
      }
      return str
    },
    countWords(str) {
      const arr = str.split(' ')

      return arr.filter(word => word !== '').length
    },
  },
}
</script>

<style scoped>
.cl-link:hover {
  cursor: pointer;
}
</style>