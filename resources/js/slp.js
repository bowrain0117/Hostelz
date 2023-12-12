import './vue/modules/sliders/listings-full-card-slider/app'
import "./vue/modules/Faqs/app";
import './vue/modules/sliders/listings-slp-slider/app'
import {hideTopHostelBookingButton, renderTableOfContent} from './src/slp'


document.addEventListener('DOMContentLoaded', function () {
    hideTopHostelBookingButton()

    renderTableOfContent()
})
