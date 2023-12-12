<div id="accordion" {{ $attributes }}>
    <div class="mt-5">
        <div id="accordion" role="tablist">
            <div class="mb-4 mt-6">
                <h4 class="text-uppercase">FAQ</h4>

                <!-- FAQ 1 -->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingOne" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne"
                            class="accordion-link collapsed">How do I ensure my reservation is tracked on
                            Hostelz.com?</a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingOne" data-parent="#accordion" class="collapse"
                        id="collapseOne" style="">
                        <div class="card-body text-content py-5">
                            <p>Ensure you're logged into Hostelz.com. Use our reservation links on hostel listings to
                                make your final reservation.</p>


                            <p><b>Hostelworld:</b><br> Use our specific links, such as <a
                                    href="https://www.hostelz.com/hw" target="_blank" rel="nofollow">hostelz.com/hw</a>,
                                to enable reservation tracking. Our system
                                will then be able to track your reservations.</p>

                            <p><b>Booking.com</b><br> You'll spot the Hostelz logo at the page's top, confirming the
                                tracking.</p>
                            <p class="mb-0"><img src="{{ routeURL('images', 'hostelz-booking-tracking.jpg') }}"></p>

                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingTwo" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo"
                            class="accordion-link collapsed">I do not see my reservation. What should I do?</a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingTwo" data-parent="#accordion" class="collapse"
                        id="collapseTwo" style="">
                        <div class="card-body text-content py-5">
                            <p class="mb-0">Just <a href="@routeURL('contact-us', ['contact-form'])" target="_blank">contact us</a>
                                and send us the following information:
                            </p>
                            <ul>
                                <li>your booking reference number</li>
                                <li>the booking platform you used</li>
                                <li>the full hostel name with city and country name</li>
                            </ul>
                            We will then take a closer look and get back to you asap.
                            <p></p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingThree" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseThree" aria-expanded="false"
                            aria-controls="collapseThree" class="accordion-link collapsed">How do I modify or cancel my
                            booking?

                        </a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingThree" data-parent="#accordion" class="collapse"
                        id="collapseThree" style="">
                        <div class="card-body text-content py-5">
                            <p class="mb-0">Directly contact the booking platform you used. Modifications or
                                cancellations aren't possible through Hostelz.com.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingFour" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseFour" aria-expanded="false"
                            aria-controls="collapseFour" class="accordion-link collapsed">Are there extra charges when
                            using Hostelz.com?</a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingFour" data-parent="#accordion" class="collapse"
                        id="collapseFour" style="">
                        <div class="card-body text-content py-5">
                            <p class="mb-0">Absolutely not! Hostelz.com is a free-to-use platform, offering hostel
                                price
                                comparisons and essential information.

                            </p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingFive" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseFive" aria-expanded="false"
                            aria-controls="collapseFive" class="accordion-link collapsed">Is my personal and reservation
                            information safe with Hostelz.com?</a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingFive" data-parent="#accordion" class="collapse"
                        id="collapseFive" style="">
                        <div class="card-body text-content d-flex align-items-center py-5">
                            <p class="mb-0">Yes, your privacy and security are of utmost importance to us. We use
                                advanced encryption and security measures to protect your data. We do not share or sell
                                your information to third parties.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingSix" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseSix" aria-expanded="false"
                            aria-controls="collapseSix" class="accordion-link collapsed">How soon after my reservation
                            can I access the freebies?</a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingSix" data-parent="#accordion" class="collapse"
                        id="collapseSix" style="">
                        <div class="card-body text-content py-5">
                            <p class="mb-0">Once your reservation is confirmed and tracked by our system, you'll gain
                                immediate access to the freebies in your Hostelz.com user profile. You will retain
                                access until three days after the last day of your stay.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 7 -->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingSeven" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseSeven" aria-expanded="false"
                            aria-controls="collapseSeven" class="accordion-link collapsed">Can I access the freebies
                            without making a reservation?</a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingSeven" data-parent="#accordion" class="collapse"
                        id="collapseSeven" style="">
                        <div class="card-body text-content py-5">
                            <p class="mb-0">The exclusive freebies are a special offer for our users who track their
                                reservations through Hostelz.com. These special guides are not available for
                                purchase.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ 8-->
                <div class="card mb-3 border-0 shadow">
                    <div id="headingEight" role="tab" class="card-header text-dark border-0 py-0">
                        <a data-toggle="collapse" href="#collapseEight" aria-expanded="true"
                            aria-controls="collapseEight" class="accordion-link">I have feedback for the platform. How
                            can I share them?</a>
                    </div>
                    <div role="tabpanel" aria-labelledby="headingEight" data-parent="#accordion" class="collapse"
                        id="collapseEight" style="">
                        <div class="card-body text-content py-5">
                            <p class="mb-0">We value user feedback immensely. Please <a href="@routeURL('contact-us', ['contact-form'])"
                                    target="_blank">get in
                                    touch</a> with us through our contact page. We are always eager to hear from our
                                community.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
