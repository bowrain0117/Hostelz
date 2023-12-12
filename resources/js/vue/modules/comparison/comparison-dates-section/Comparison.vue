<template>
  <div v-if="mobile">
    <h3 class="cl-dark font-weight-bold mb-4 col-lg-3 container comparison-prices-text">Compare Prices</h3>

    <div class="sticky-compare-dates d-flex justify-content-center">
      <ul role="tablist" class="nav nav-tabs container comparison-tabs">
        <li
            v-for="(listing, id, index) in listings"
            class="nav-item"
        >
          <a
              :id="`tab-${listing.id}-tab`"
              data-toggle="tab"
              :href="`#tab-${listing.id}-content`"
              role="tab"
              :aria-controls="`tab-${listing.id}-content`"
              aria-selected="true"
              class="nav-link bg-primary-light py-2 px-sm-4 font-weight-600 display-4"
              :class="{ 'show active': index === 0 }"
              @click="changeTabHandler(listing.id)"
          >
            {{ shortenName(listing.name) }}
          </a>
        </li>
      </ul>
    </div>

    <div class="container tab-content mt-4">
      <button class="btn-clear mb-4 cursor-pointer" data-toggle="modal" data-target="#comparison-dates">
        <svg-icon :width="24" :height="24" :icon="'filters-icon'"></svg-icon>
        <span class="cl-dark pl-2">Filter</span>
      </button>

      <div
          class="mb-4 tab-pane fade features show active"
          :id="`tab-${listing.id}-content`"
          role="tabpanel"
          :aria-labelledby="`tab-${listing.id}-content`"
      >
        <div v-for="(room, roomName) in updatedRoomTypes">
          <div v-if="rooms[listing.id] !== undefined && rooms[listing.id][roomName] !== undefined">
            <a
                class="d-flex align-items-center accordion-link cl-dark collapse-arrow-wrap collapsed py-0 tx-body font-weight-600 mb-4"
                type="button"
                data-toggle="collapse"
                :data-target="`#${room.roomInfo.code}-collapse`"
                aria-expanded="true"
                :aria-controls="`${room.roomInfo.code}-collapse`"
            >
              <i class="fas fa-angle-down float-right pr-2"></i>
              <i class="fas fa-angle-up float-right pr-2"></i>
              {{ roomName }}
            </a>

            <div class="collapse multi-collapse border-bottom cl-dark mb-4 show" :id="`${room.roomInfo.code}-collapse`">
              <div class="col-4">
                <div v-for="(room, system) in rooms[listing.id][roomName]">
                  <a
                      v-if="system !== 'ensuite'"
                      :href="room['bookingPageLink']"
                      class="rounded-sm p-2-1 d-flex align-items-center flex-row text-decoration-none"
                      target="_blank"
                      rel="nofollow"
                  >
                    <svg-icon :width="22" :height="22" :icon="systemName(system)"></svg-icon>
                    <span class="cl-text font-weight-600 ml-1">{{ room['price'] }}</span>
                  </a>
                </div>
                <div class="p-2-1 d-flex align-items-center text-gray-600">
                  <span v-if="rooms[listing.id][roomName]['ensuite']">Ensuite Bathroom</span>
                  <span v-else>Shared Bathroom</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div v-else>
    <div class="sticky-compare-dates d-flex justify-content-center pt-3">
      <div class="align-items-baseline d-flex row container">
        <h3 class="cl-dark font-weight-bold mb-4 col-lg-3 comparison-prices-text">Compare Prices</h3>

        <div class="col-lg-3 cl-dark font-weight-bold" v-for="listing in listings">
          {{ listing.name }}
        </div>
      </div>
    </div>

    <div class="container">
      <button class="btn-clear mb-4 cursor-pointer" data-toggle="modal" data-target="#comparison-dates">
        <svg-icon :width="24" :height="24" :icon="'filters-icon'"></svg-icon>
        <span class="cl-dark pl-2">Filter</span>
      </button>

      <div class="mb-4" v-for="(roomType, roomKey) in updatedRoomTypes">
        <a
            class="d-flex align-items-center accordion-link cl-dark collapse-arrow-wrap collapsed py-0 tx-body font-weight-600 mb-4"
            type="button"
            data-toggle="collapse"
            :data-target="`#compare-${roomType.roomInfo.code}-collapse`"
            aria-expanded="true"
            :aria-controls="`compare-${roomType.roomInfo.code}-collapse`"
        >
          <i class="fas fa-angle-down float-right pr-2"></i>
          <i class="fas fa-angle-up float-right pr-2"></i>
          {{ roomType.roomInfo.name }}
        </a>
        <div class="multi-collapse border-bottom cl-dark collapse show" :id="`compare-${roomType.roomInfo.code}-collapse`">
          <div class="row mb-4">
            <div class="col-12">
              <div class="offset-3 col-9">
                <div class="row">
                  <div class="col-4" v-for="listing in listings">
                    <div v-if="rooms[listing.id] !== undefined && rooms[listing.id][roomKey] !== undefined">
                      <div
                          v-for="(room, system) in rooms[listing.id][roomKey]"
                      >
                        <a
                            v-if="system !== 'ensuite'"
                            :href="room['bookingPageLink']"
                            class="rounded-sm p-2-1 d-flex align-items-center flex-row text-decoration-none"
                            target="_blank"
                            rel="nofollow"
                        >
                          <svg-icon :width="22" :height="22" :icon="systemName(system)"></svg-icon>
                          <span class="cl-text font-weight-600 ml-1">{{ room['price'] }}</span>
                        </a>
                      </div>
                      <div class="p-2-1 d-flex align-items-center text-gray-600">
                        <span v-if="rooms[listing.id][roomKey]['ensuite']">Ensuite Bathroom</span>
                        <span v-else>Shared Bathroom</span>
                      </div>
                    </div>
                    <div
                        v-else
                        class="p-2-1 d-flex align-items-center text-muted"
                    >
                      No availability
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="modal fade" id="comparison-dates" tabindex="-1" role="dialog" aria-labelledby="comparison-dates" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered position-relative justify-content-center" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="sb-title mb-0">Filters</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <svg-icon :width="24" :height="24" :icon="'close-icon-2'"></svg-icon>
          </button>
        </div>

        <div class="modal-body datepicker-modal d-flex justify-content-center">
          <div class="container">
            <div class="form-group mb-1" v-for="(room, roomName) in roomTypes">
              <div class="custom-control custom-checkbox">
                <input
                    class="custom-control-input comparison-dates-input"
                    type="checkbox"
                    :id="room.roomInfo.code"
                    :name="roomName"
                    :value="roomName"
                    :data-label="roomName"
                    checked
                    @click="chooseOption"
                >
                <label
                    :for="room.roomInfo.code"
                    class="custom-control-label tx-small cursor-pointer filter-option">
                  {{ roomName }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer justify-content-between">
          <button
              @click="clickModalClear"
              type="button"
              class="btn btn-lg btn-light rounded px-4 text-uppercase"
          >
            clear
          </button>
          <button
              type="button"
              class="btn btn-lg btn-primary rounded px-4 text-uppercase d-flex align-items-center"
              data-toggle="modal"
              data-target="#comparison-dates"
          >
            Show results
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapMutations, mapState } from "vuex";

export default {
  name: 'Comparison',
  props: ['listings', 'roomTypes', 'rooms'],
  data() {
    return {
      updatedRoomTypes: {},
      mobile: false,
      smallMobile: false,
    }
  },
  methods: {
    ...mapActions('comparisonModule', [
      'changeTab',
    ]),
    ...mapMutations('comparisonModule', [
      'set',
    ]),
    handleScroll() {
      let stickyCompare = document.querySelector('.sticky-compare-dates')
      const observer = new IntersectionObserver(
          ([e]) => e.target.classList.toggle('sticky-border', e.intersectionRatio < 1),
          { threshold: [1] }
      );

      observer.observe(stickyCompare);
    },
    handleResize() {
      if (window.innerWidth < 393) {
        this.mobile = true
        this.smallMobile = true
        return
      }

      if (window.innerWidth < 992) {
        this.smallMobile = false
        this.mobile = true
        return
      }

      this.smallMobile = false
      this.mobile = false
    },
    systemName(system) {
      return system.toLowerCase() + '-icon-sm'
    },
    chooseOption(e) {
      let key = e.target.dataset.label

      if (this.updatedRoomTypes.hasOwnProperty(key)) {
        delete this.updatedRoomTypes[key]
        return
      }

      this.updatedRoomTypes[key] = this.roomTypes[key]
    },
    removeAllChecked() {
      document.querySelectorAll('.comparison-dates-input').forEach(input => input.checked = false)
    },
    clickModalClear() {
      this.updatedRoomTypes = {}

      this.removeAllChecked()
    },
    changeTabHandler(id) {
      this.changeTab(id)
    },
    removeContainer() {
      document.querySelector('#compare').classList.remove('container')
    },
    shortenName(name) {
      if (this.smallMobile) {
        return name
      }

      return name.substring(0, 10) + '...'
    },
  },
  mounted() {
    window.addEventListener('scroll', this.handleScroll)
    window.addEventListener('resize', this.handleResize)

    this.updatedRoomTypes = { ...this.roomTypes }

    this.handleResize()
    this.changeTab()
    this.removeContainer()
  },
  destroyed () {
    window.removeEventListener('scroll', this.handleScroll)
    window.removeEventListener('resize', this.handleResize)
  },
  created() {
    this.set(['listings', this.listings])
  },
  computed: {
    ...mapState('comparisonModule', [
      'listings',
      'listing',
    ]),
  }
}
</script>

<style scoped>
.comparison-tabs .nav-link {
  color: #ff635c;
}

.comparison-tabs .nav-link:hover {
  color: #FAFBFE;
  background-color: #ff635c;
}

.comparison-tabs .nav-link.active {
  color: #FAFBFE;
  background-color: #4A5268;
}

.nav-tabs .nav-link.active,
.nav-tabs .nav-item.show .nav-link {
  border-bottom: none;
}

.sticky-compare-dates {
  position: sticky;
  top: -1px;
  z-index: 11;
}

.sticky-border {
  background: #fff;
  box-shadow: 5px 5px 20px rgb(137 141 154 / 15%);
}

.comparison-prices-text {
  padding: 0;
}

@media screen and (max-width: 991px) {
  .comparison-tabs {
    padding-left: 15px;
  }
  .sticky-border {
    padding-top: 51px;
  }
  .comparison-prices-text {
    padding-left: 15px;
  }
}

@media screen and (max-width: 392px) {
  .comparison-tabs {
    flex-direction: column;
  }
}
</style>