import axios from "axios"

export const actions = {
    getSortedReviews({commit, state}) {
        axios
            .get('/listing-reviews/' + state.listing.id + '?sortBy=' + state.sortedBy + '&search=' + state.searchString + '&page=' + state.currentPage)
            .then((data) => {
                commit('set', ['sortedReviews', data.data.reviews])
                commit('set', ['pagesNumber', data.data.pagesNumber])
            })
    },
}
