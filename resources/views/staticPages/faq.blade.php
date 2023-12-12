<?php
    Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true ])

@section('title', langGet('SeoInfo.FAQMetaTitle', [ 'year' => date("Y")]))

@section('header')
    <meta name="description" content="{!! langGet('SeoInfo.FAQMetaDescription', [ 'year' => date("Y")]) !!}">
@stop

@section('content')
  <section>
      <div class="container">
          <div class="col-12 mb-lg-6 mb-6 px-0">
              <!--  Breadcrumbs  -->
              <ul class="breadcrumb black px-0 mx-lg-0">
                  {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                  {!! breadcrumb(langGet('faq.FAQ')) !!}
              </ul>
              <h1 class="mb-3 mb-lg-5 pb-md-2">@langGet('faq.FAQ')</h1>

              <div id="accordion" class="">
                <div class="mt-5">
                  <div id="accordion" role="tablist">

                    <div class="card border-0 shadow mb-3">
                      <div id="headingOne" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne" class="accordion-link">What is Hostelz.com?</a></div>
                      <div id="collapseOne" role="tabpanel" aria-labelledby="headingOne" data-parent="#accordion" class="collapse show">
                        <div class="card-body py-5 text-content text-content">
                          <p class="mb-0">Simply, Hostelz is your answer to a smart hostel price comparison that lists every single hostel in the world - discover the best deal in one easy click. We compare prices from major booking sites including booking.com, Hostelworld and Hostelsclub. Check out our <a href="@routeURL('about')" title="about hostelz">About Us page</a> for a detailed overview of what we do.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingThirteen" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseThirteen" aria-expanded="false" aria-controls="collapseThirteen" class="accordion-link collapsed">@langGet('about.HowCanISave.title')</a></div>
                      <div id="collapseThirteen" role="tabpanel" aria-labelledby="headingThirteen" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content">
                          <p class="m">@langGet('about.HowCanISave.subtitle', ['href' => routeURL('articles', 'cheapest-hostel-booking-website')])</p>
                          <div class="d-flex my-4">
                              <img src="/pics/articles/originals/57/1778257.png" alt="#" class="mw-100 m-auto">
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingTwo" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" class="accordion-link collapsed">How do you list hostels?</a></div>
                      <div id="collapseTwo" role="tabpanel" aria-labelledby="headingTwo" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content">
                          <p class="mb-0">We list all hostels in the world completely free of charge. Even if a hostel is not using a booking website (but has their own website or social media profile), we will list it on Hostelz.com. Hostel owners and travellers can add hostels directly via <a href="@routeURL('submitNewListing')">this page</a>. </p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingThree" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree" class="accordion-link collapsed">Do you favor listings when paying higher commissions?</a></div>
                      <div id="collapseThree" role="tabpanel" aria-labelledby="headingThree" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">No. Hostels do not have the possibility to pay higher commission or fix payments to be favored on Hostelz.com. We do not charge hostels for listing their property on our page, nor do we charge our readers for using our website to find hostels. Therefore, none of our listings are favoured by us on a profit basis. Our hostels are all rated based on genuine customer reviews.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingFour" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseFour" aria-expanded="false" aria-controls="collapseFour" class="accordion-link collapsed">Where do the ratings at Hostelz.com come from?</a></div>
                      <div id="collapseFour" role="tabpanel" aria-labelledby="headingFour" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">A hostel rating represents all of the genuine customer reviews for that property. We pull data from major booking websites (Booking.com, Hostelworld & Hostelsclub), and also allow Hostelz.com members to leave reviews directly on the listing page. Our system automatically updates the rating as more reviews are left.
                          <br>
                          <br>
                          Hostelz.com Score is on a 10 point scale (it is not a percentage). It is based on not only ratings, but also calculates in other factors such as the total number of reviews (a listing with many positive reviews will have a higher rating than a listing with only a few).</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingFive" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseFive" aria-expanded="false" aria-controls="collapseFive" class="accordion-link collapsed">Why is Hostelz.com the easiest way to find cheaper hostels?</a></div>
                      <div id="collapseFive" role="tabpanel" aria-labelledby="headingFive" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">We show you the current prices in all of the booking systems at once so that you can make your booking on whichever site has the lowest price. Because prices vary between the booking sites, you can often save >20% off the price of the same bed or room. Just remember that Hostelz.com is not a booking site. We simply compare rates for you so you can travel for cheaper and longer.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingSix" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseSix" aria-expanded="false" aria-controls="collapseSix" class="accordion-link collapsed">How does Hostelz.com earn money?</a></div>
                      <div id="collapseSix" role="tabpanel" aria-labelledby="headingSix" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">We use affiliate links with the major booking websites and receive a small percentage of each booking made through our site. This means that there is absolutely no extra cost to the user for using our site.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingSeven" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven" class="accordion-link collapsed">How many hostels are listed at Hostelz.com?</a></div>
                      <div id="collapseSeven" role="tabpanel" aria-labelledby="headingSeven" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">Every single hostel in the world is listed! Hostelz.com is the only worldwide hostel guide that lists ALL hostels (for free). So to put it simply… thousands! If you notice one is missing, please let us know and <a href="@routeURL('submitNewListing')">add it here</a>.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingEight" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseEight" aria-expanded="false" aria-controls="collapseEight" class="accordion-link collapsed">I am a Journalist. How can I get in touch with Hostelz.com Team?</a></div>
                      <div id="collapseEight" role="tabpanel" aria-labelledby="headingEight" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">We are open to interviews and media inquiries about hostels. As hostel experts we can share our years of experience as well as unique data and statistics on hostels. Get in touch with us and send your enquiries using <a href="@routeURL('contact-us', [ 'contact-form', 'press'])" title="press hostels information data">this form</a>.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingTen" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseTen" aria-expanded="false" aria-controls="collapseTen" class="accordion-link collapsed">Can I book hostels at Hostelz.com?</a></div>
                      <div id="collapseTen" role="tabpanel" aria-labelledby="headingTen" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">Hostelz.com is a price comparison site and can be used to help you find the best and cheapest hostel, fast! It is not a direct hostel booking website. Instead, you will be taken to your preferred booking platform such as Hostelworld, for example, to complete your booking.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingSeventeen" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseSeventeen" aria-expanded="false" aria-controls="collapseSeventeen" class="accordion-link collapsed">@langGet('faq.ReservationWork.title')</a></div>
                      <div id="collapseSeventeen" role="tabpanel" aria-labelledby="headingSeventeen" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content align-items-center">
                          <p class="">@langGet('faq.ReservationWork.p-1')</p>
                          <p class="">@langGet('faq.ReservationWork.p-2')</p>
                          <p class="mb-0">@langGet('faq.ReservationWork.p-3')</p>
                        </div>
                      </div>
                    </div>


                    <div class="card border-0 shadow mb-3">
                      <div id="headingEleven" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseEleven" aria-expanded="false" aria-controls="collapseEleven" class="accordion-link collapsed">Is Hostelz.com a safe website to use?</a></div>
                      <div id="collapseEleven" role="tabpanel" aria-labelledby="headingEleven" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">Yes, Hostelz.com is safe! Our team are hostel experts and ensure Hostelz.com is secure and safe to use. As we are a price comparison website, there will never be a time you need to enter delicate information such as credit card details.</p>
                        </div>
                      </div>
                    </div>

                    <div class="card border-0 shadow mb-3">
                      <div id="headingTwelve" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseTwelve" aria-expanded="false" aria-controls="collapseTwelve" class="accordion-link collapsed">How can I sign up at Hostelz.com as a traveler?</a></div>
                      <div id="collapseTwelve" role="tabpanel" aria-labelledby="headingTwelve" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">Creating an account with Hostelz.com is as easy as filling in <a href="@routeURL('login')">this form</a>. Once you become a member, you can post comments, leave hostel reviews and track any bookings you make using our site. You can also earn points which enables you to access exclusive features - coming soon.</p>
                        </div>
                      </div>
                    </div>

                  <div class="mt-6 mb-4">
                      <h5 class="text-uppercase">For Hostel Owners and Managers</h5>
                      <p>Below we list frequently asked questions for hostel managers and owners.</p>
                  </div>

                  <div class="card border-0 shadow mb-3">
                      <div id="headingNine" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseNine" aria-expanded="false" aria-controls="collapseNine" class="accordion-link collapsed">I run a Hostel and received a negative comment from a user on Hostelz.com. What can I do?</a></div>
                      <div id="collapseNine" role="tabpanel" aria-labelledby="headingNine" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">Customers are entitled to leave an honest review of their experience at your property. As a hostel owner, comments can be used to improve your accommodation. If the comment seems suspicious and not genuine, please get in touch with us. Hostel owners can also respond to reviews from their claimed listing.</p>
                        </div>
                      </div>
                  </div>

                  <div class="card border-0 shadow mb-3">
                      <div id="headingFourteen" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseFourteen" aria-expanded="false" aria-controls="collapseFourteen" class="accordion-link collapsed">Can I delist my hostel from Hostelz.com?</a></div>
                      <div id="collapseFourteen" role="tabpanel" aria-labelledby="headingFourteen" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">When your hostel is listed at Booking.com and partners, Hostelworld and partners or at Hostelsclub.com and partners, your hostel will be automatically listed at Hostelz.com. This listing cannot be removed and is covered by the Terms&Conditions for being listed with any of the mentioned Online Travel Agencies.</p>
                        </div>
                      </div>
                  </div>

                  <div class="card border-0 shadow mb-3">
                      <div id="headingFifteen" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseFifteen" aria-expanded="false" aria-controls="collapseFifteen" class="accordion-link collapsed">Can I manage my Hostel Listing and edit information?</a></div>
                      <div id="collapseFifteen" role="tabpanel" aria-labelledby="headingFifteen" data-parent="#accordion" class="collapse">
                        <div class="card-body py-5 text-content d-flex align-items-center">
                          <p class="mb-0">Yes. Every owner can claim their listing. <a href="@routeURL('contact-us')">Contact us</a> for information about how to manage your listing. Please use the official hostel email address for the verification process.</p>
                        </div>
                      </div>
                  </div>

                    <div class="card border-0 shadow mb-3">
                        <div id="headingSixteen" role="tab" class="card-header text-dark border-0 py-0"><a data-toggle="collapse" href="#collapseSixteen" aria-expanded="false" aria-controls="collapseSixteen" class="accordion-link collapsed">How can I improve the Hostelz.com Score of my property?</a></div>
                        <div id="collapseSixteen" role="tabpanel" aria-labelledby="headingSixteen" data-parent="#accordion" class="collapse">
                            <div class="card-body py-5 text-content d-flex align-items-center">
                                <p class="mb-0">You can actively encourage your guests to leave reviews with Hostelz.com. Every positive review helps to improve your overall Hostelz.com Score. Please note that our system detects fake reviews. Reviews left on Hostelz.com require a verified stay, email address and user account with Hostelz.com.</p>
                            </div>
                        </div>
                    </div>


                  </div>
                </div>
              </div>
          </div>
      </div>
  </section>
@stop

@section('pageBottom')
  @parent

  <script>
    initializeTopHeaderSearch();
  </script>
@stop