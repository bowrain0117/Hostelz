import { getters } from "./getters"
import { actions } from './actions'
import { mutations } from "./mutations"
import { state } from "./state"

export const listingReviewsModules = {
    namespaced: true,
    state,
    getters,
    actions,
    mutations,
}
