<template>
  <div v-if="mobile">
    <h3 class="cl-dark font-weight-bold mb-4 col-lg-3 container comparison-prices-text">Let's dig deeper</h3>

    <div class="sticky-compare d-flex justify-content-center">
      <ul role="tablist" class="nav nav-tabs container comparison-tabs">
        <li
            v-for="(listing, id, index) in listings"
            class="nav-item"
        >
          <a
              :id="`${id}-tab`"
              data-toggle="tab"
              :href="`#${id}-content`"
              role="tab"
              :aria-controls="`tab${id}-content`"
              aria-selected="true"
              class="nav-link bg-primary-light py-2 px-sm-4 font-weight-600 display-4"
              :class="{ 'show active': index === 0 }"
              @click="changeTabHandler(id)"
          >
            {{ shortenName(listing.name) }}
          </a>
        </li>
      </ul>
    </div>

    <div class="container tab-content mt-4">
      <button class="btn-clear mb-4 cursor-pointer" data-toggle="modal" data-target="#comparison-filters">
        <svg-icon :width="24" :height="24" :icon="'filters-icon'"></svg-icon>
        <span class="cl-dark pl-2">Filter</span>
      </button>

      <div
          class="mb-4 tab-pane fade features show active"
          :id="`${listing.id}-content`"
          role="tabpanel"
          :aria-labelledby="`${listing.id}-content`"
      >

        <div v-for="(options, option) in updatedFeatures">
          <a
              class="d-flex align-items-center accordion-link cl-dark collapse-arrow-wrap collapsed py-0 tx-body font-weight-600 mb-4"
              type="button"
              data-toggle="collapse"
              :data-target="`#${option}-collapse`"
              aria-expanded="true"
              :aria-controls="`${option}-collapse`"
          >
            <i class="fas fa-angle-down float-right pr-2"></i>
            <i class="fas fa-angle-up float-right pr-2"></i>
            {{ optionName(option) }}
          </a>

          <div class="collapse multi-collapse border-bottom cl-dark mb-4 show" :id="`${option}-collapse`">
            <div class="row mb-4" v-for="(feature, key) in options">
              <div class="col-6">{{ feature }}</div>
              <div class="col-6" v-if="option === 'facilities'">
                <div v-if="listing.facilities.hasOwnProperty(key)">
                  {{ listing.facilities[key] }}
                </div>
                <div v-else>
                  No data
                </div>
              </div>
              <div class="col-6" v-else>
                <div v-if="Array.isArray(listing.keyFeatures[key])" v-for="keyFeature in listing.keyFeatures[key]">
                  {{ keyFeature }}
                </div>
                <div v-else-if="!listing.keyFeatures[key] || listing.keyFeatures[key].toLowerCase() === 'no'" class="text-muted">No data</div>
                <div v-else>{{ listing.keyFeatures[key] }}</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div v-else>
    <div class="sticky-compare d-flex justify-content-center pt-3">
      <div class="align-items-baseline d-flex row container">
        <h3 class="p-0 cl-dark font-weight-bold mb-4 col-lg-3 comparison-prices-text">Let's dig deeper</h3>

        <div class="col-lg-3 cl-dark font-weight-bold" v-for="listing in listings">
          <div>{{ listing.name }}</div>
          <a href="#comparePrices" class="text-primary text-sm" data-smooth-scroll>Add your dates</a>
        </div>
      </div>
    </div>

    <div class="container">
      <button class="btn-clear mb-4 cursor-pointer" data-toggle="modal" data-target="#comparison-filters">
        <svg-icon :width="24" :height="24" :icon="'filters-icon'"></svg-icon>
        <span class="cl-dark pl-2">Filter</span>
      </button>

      <div class="features">

        <div class="mb-4" v-for="(options, option) in updatedFeatures">
          <a
              class="d-flex align-items-center accordion-link cl-dark collapse-arrow-wrap collapsed py-0 tx-body font-weight-600 mb-4"
              type="button"
              data-toggle="collapse"
              :data-target="`#${option}-collapse`"
              aria-expanded="true"
              :aria-controls="`${option}-collapse`"
          >
            <i class="fas fa-angle-down float-right pr-2"></i>
            <i class="fas fa-angle-up float-right pr-2"></i>
            {{ optionName(option) }}
          </a>

          <div class="collapse multi-collapse border-bottom cl-dark show" :id="`${option}-collapse`">
            <div class="row mb-4" v-for="(feature, key) in options">
              <div class="col-lg-3">{{ feature }}</div>
              <div class="col-lg-3" v-for="listing in listings" v-if="option === 'facilities'">
                <div v-if="listing.facilities.hasOwnProperty(key)">
                  {{ listing.facilities[key] }}
                </div>
                <div v-else class="text-muted">
                  No data
                </div>
              </div>
              <div class="col-lg-3" v-for="listing in listings" v-else>
                <div v-if="Array.isArray(listing.keyFeatures[key])" v-for="keyFeature in listing.keyFeatures[key]">
                  {{ keyFeature }}
                </div>
                <div v-else-if="!listing.keyFeatures[key] || listing.keyFeatures[key] === 'no'" class="text-muted">No data</div>
                <div v-else>{{ listing.keyFeatures[key] }}</div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <div class="modal fade" id="comparison-filters" tabindex="-1" role="dialog" aria-labelledby="comparison-filters" aria-hidden="true">
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
            <div
                v-for="(options, option) in features"
                class="row mb-3 filter-wrap border-bottom pb-3"
                :id="`filter-${option}`"
            >
              <div class="col">
                <h4 class="cl-text title-4 font-weight-600 mb-2 pb-1" data-toggle="collapse" :data-target="`#filter-collapse-${option}`" style="cursor: pointer">
                <span :id="`filter-title-${option}`">
                  {{ option === 'facilities' ? 'Facilities' : 'Key Features' }}
                </span>
                  <button
                      class="btn-clear float-right arrow-collapse collapsed"
                      type="button"
                      data-toggle="collapse"
                      :data-target="`#filter-collapse-${option}`"
                      aria-expanded="true"
                      :aria-controls="`filter-collapse-${option}`"
                  >
                    <svg-icon :width="24" :height="24" :icon="'arrow-bottom'"></svg-icon>
                  </button>
                </h4>

                <div
                    :id="`filter-collapse-${option}`"
                    class="collapse show"
                    data-parent=".modal-body"
                >
                  <div class="form-group mb-1" v-for="(feature, key) in options">
                    <div class="custom-control custom-checkbox">
                      <input
                          class="custom-control-input comparison-input"
                          type="checkbox"
                          :id="key"
                          :name="option"
                          :value="feature"
                          :data-label="key"
                          :data-option="option"
                          @click="chooseOption"
                      >
                      <label
                          :for="key"
                          class="custom-control-label tx-small cursor-pointer filter-option">
                        {{ feature }}
                      </label>
                    </div>
                  </div>
                </div>
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
              data-target="#comparison-filters"
          >
            Show results
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapState } from "vuex";

export default {
  name: 'HostelCards',
  data() {
    return {
      distanceToTop: 0,
      mobile: false,
      smallMobile: false,
      updatedFeatures: {
        keyFeatures: {},
        facilities: {},
      },
      defaultKeyFeatures: [
        'breakfast',
        'distance',
        'great',
        'rating'
      ],
      defaultFacilities: [
        'bikeRental',
        'tours',
        'pubCrawls',
        'parking',
        'towels',
        'sheets',
        'lockersInCommons',
        'luggageStorage',
        'wifiCommons',
        'ac',
        'bar',
        'walking_tours',
        'airportPickup',
      ],
    }
  },
  methods: {
    ...mapActions('comparisonModule', [
      'changeTab',
    ]),
    handleScroll() {
      let stickyCompare = document.querySelector('.sticky-compare')

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
    chooseOption(e) {
      let key = e.target.dataset.label
      let option = e.target.dataset.option

      if (this.updatedFeatures[option].hasOwnProperty(key)) {
        delete this.updatedFeatures[option][key]
        return
      }

      this.updatedFeatures[option][key] = this.features[option][key]
    },
    createNewFeaturesObject() {
      Object.entries(this.features.keyFeatures).forEach(([key, value]) => {
        this.updatedFeatures.keyFeatures[key] = value
      })

      Object.keys(this.features.facilities).forEach((key) => {
        if (this.defaultFacilities.includes(key)) {
          this.updatedFeatures.facilities[key] = this.features.facilities[key]
        }
      })
    },
    removeAllChecked() {
      document.querySelectorAll('.comparison-input').forEach(input => input.checked = false)
    },
    createCheckedButtons() {
      this.defaultKeyFeatures.forEach((value) => {
        document.querySelector(`#${value}`).checked = true
      })

      this.defaultFacilities.forEach((value) => {
        document.querySelector(`#${value}`).checked = true
      })
    },
    clickModalClear() {
      this.updatedFeatures.keyFeatures = {}
      this.updatedFeatures.facilities = {}

      this.removeAllChecked()
    },
    changeTabHandler(id) {
      this.changeTab(id)
    },
    optionName(option) {
      return option === 'facilities' ? 'Facilities' : 'Key Features'
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

    this.changeTab()

    this.handleResize()

    this.createNewFeaturesObject()
    this.createCheckedButtons()
  },
  destroyed () {
    window.removeEventListener('scroll', this.handleScroll)
    window.removeEventListener('resize', this.handleResize)
  },
  computed: {
    ...mapState('comparisonModule', [
      'listings',
      'features',
      'listing',
    ]),
  },
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

.sticky-compare {
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