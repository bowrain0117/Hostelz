import {createApp} from 'vue';

import ComparisonIcon from "./ComparisonIcon";
import {store} from '../../../store'

createApp({})
    .use(store)
    .component('comparison-icon', ComparisonIcon)
    .mount('.vue-comparison-icon');