import axios from "axios"

export const actions = {
    addListingToCompare({commit, state, dispatch}, listingId) {
        axios
            .post('/compare/' + listingId)
            .then((data) => {
                dispatch('addCountToButton', data)
            })
    },
    removeListingFromCompare({commit, state, dispatch}, listingId) {
        axios
            .delete('/compare/' + listingId)
            .then((data) => {
                dispatch('addCountToButton', data)

                window.location.href = data.data.href
            })
    },
    addCountToButton({}, data) {
        let loggedOutComparisonCount = document.querySelector('#loggedOut .comparison-count')
        let dropdownMenuComparisonCount = document.querySelector('.dropdown-item .comparison-count')
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
    },
    changeTab({commit, state}, id) {
        if (id === undefined) {
            commit('set', ['listing', state.listings[Object.keys(state.listings)[0]]])
            return
        }

        commit('set', ['listing', state.listings[id]])
    },
}