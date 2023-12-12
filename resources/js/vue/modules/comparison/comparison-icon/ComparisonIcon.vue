<template>
  <div
      v-if="active"
      class="position-relative"
  >
    <div
        class="card-fav-icon ml-1 opacity-9 position-relative z-index-2 cursor-pointer comparison bg-dark"
        @click="handleClick"
    >
      <svg-icon :width="20" :height="20" :icon="'comparison-tool-white'"></svg-icon>
    </div>
    <div
        v-if="activeMessage"
        class="alert alert-warning comparison-alert text-left"
    >
      <div v-if="count === 1">Add more hostels to compare!</div>

      <div v-if="count === 2">
        <div class="d-inline">Compare up to {{ maxCount }} hostels.</div>
        <a href="/compare" className="cl-link"> Compare now!</a>
      </div>

      <div v-if="count === 3">
        <a href="/compare" className="cl-link">Compare</a>
        <div> all {{ maxCount }} hostels now!</div>
      </div>
    </div>
  </div>
  <div
      v-else
      class="position-relative"
  >
    <div
        class="card-fav-icon ml-1 opacity-9 position-relative z-index-2 cursor-pointer bg-light comparison"
        @click="handleClick"
    >
      <div class="comparison-tooltip">Add to Hostel Comparizon tool</div>
      <svg-icon :width="20" :height="20" :icon="'comparison-tool'"></svg-icon>
    </div>
    <div
        v-if="activeMessage"
        class="alert alert-warning comparison-alert text-left"
    >
      <div v-if="count === 4">
        <div class="d-inline">You can compare {{ maxCount }} hostels at once!</div>
        <a href="/compare" className="cl-link"> Compare now!</a>
      </div>
    </div>
  </div>

</template>

<script>
import axios from "axios"
import SvgIcon from '../../../components/Icon.vue'

export default {
  name: 'ComparisonIcon',
  props: ['listingId'],
  components: {
    SvgIcon
  },
  data() {
    return {
      active: false,
      count: 0,
      maxCount: 3,
      activeMessage: false,
    }
  },
  methods: {
    handleClick() {
      this.active ? this.removeListingFromCompare() : this.addListingToCompare()
    },
    addListingToCompare() {
      axios
          .post('/compare/' + this.listingId)
          .then((data) => {
            this.count = data.data.count
            this.activeMessage = !this.activeMessage
            setTimeout(() => this.activeMessage = !this.activeMessage, 5000)

            if (this.count > this.maxCount) {
              return
            }

            this.addCountToButton(data)
            this.active = !this.active
          })
    },
    removeListingFromCompare() {
      axios
          .delete('/compare/' + this.listingId)
          .then((data) => {
            this.addCountToButton(data)
          })

      this.active = !this.active
    },
    addCountToButton(data) {
      let loggedOutComparisonCount = document.querySelector('#loggedOut .comparison-count')
      let dropdownMenuComparisonCount = document.querySelector('#loggedIn .comparison-count')
      let stickyMobileCount = document.querySelector('.comparison-sticky-mobile .comparison-count')

      if (stickyMobileCount !== null) {
        stickyMobileCount.innerHTML = data.data.count
      }

      if (loggedOutComparisonCount !== null) {
        loggedOutComparisonCount.innerHTML = data.data.count
      }

      if (dropdownMenuComparisonCount !== null) {
        dropdownMenuComparisonCount.innerHTML = data.data.count
      }
    }
  },
  mounted() {
    const comparisonIds = localStorage.comparisonIds.split(',')

    if (Object.values(comparisonIds).includes(this.listingId)) {
      this.active = !this.active
    }
  }
}
</script>

<style scoped>
.card-fav-icon {
  opacity: 1;
}

.comparison-alert {
  min-width: 200px;
  font-size: 16px;
  position: absolute;
  top: 0;
  right: 18px;
  z-index: 100;
}

.comparison-alert > div {
  line-height: 1.5;
}

.comparison-tooltip {
  display: none;
}

.comparison:hover .comparison-tooltip {
  display: block;
  background: #000;
  position: absolute;
  top: 45px;
  right: 50%;
  z-index: 999;
  color: #fff;
  padding: 0 20px;
  border-radius: 5px;
  font-size: 14px;
  min-width: max-content;
  transform: translateX(50%);
}

.comparison:hover .comparison-tooltip::before {
  content: '';
  width: 0;
  height: 0;
  position: absolute;
  top: -5px;
  left: 50%;
  transform: translateX(-50%);
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-bottom: 5px solid black;
}
</style>