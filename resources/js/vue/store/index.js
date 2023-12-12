import Vuex from 'vuex'
import { listingReviewsModules } from "./modules/listing-reviews-module"
import { comparisonModule } from "./modules/comparison-module"

export const store = new Vuex.Store({
    modules: {
        listingReviewsModules,
        comparisonModule
    },
})
