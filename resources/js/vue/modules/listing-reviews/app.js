import { createApp } from 'vue';

//components
import CommunityReviews from './CommunityReviews.vue'
import Reviews from './Reviews.vue'
import SearchSortReviews from './SearchSortReviews.vue'
import Comment from './Comment.vue'
import Pagination from './Pagination.vue'
import Icon from '../../components/Icon.vue'
import { store } from '../../store'


createApp({})
    .use(store)
    .component('listing-reviews', CommunityReviews)
    .component('reviews', Reviews)
    .component('pagination', Pagination)
    .component('comment', Comment)
    .component('svg-icon', Icon)
    .component('search-sort-reviews', SearchSortReviews)
    .mount('#vue-listing-reviews');