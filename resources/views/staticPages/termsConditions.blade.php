<?php
Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', ['showHeaderSearch' => true])

@section('title', 'Terms & Conditions - Hostelz.com')


@section('header')
    <meta name="description" content="">
    <meta name="robots" content="noindex, nofollow">
@stop

@section('content')
    <section>
        <div class="container">
            <div class="col-12 mb-lg-6 mb-6 px-0">

                <ul class="breadcrumb black mx-lg-0 px-0">
                    {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                    {!! breadcrumb('Terms & Conditions') !!}
                </ul>

                <div class="mb-4">
                    <h1 class="mb-lg-5 pb-md-2 mb-3">Terms and Conditions</h1>
                    <p class="mb-3">Welcome to Hostelz.com, a Price Comparison Website for Hostels and Budget Accommodation
                        ("Website" or "Provider"). By using this Website, you (“User”) agree to comply with and be bound by
                        the following Terms and Conditions. Please review them carefully.</p>
                    <h2 class="h3 mb-1">Summary of Key Points</h2>
                    <p class="mb-3">
                    <ul>
                        <li class="mb-2"><b>Organic Sorting of Listings</b>: Listings are sorted primarily by relevance
                            and ratings. Personalization is available for signed-up members only. Commissions or third-party
                            benefits do not influence the listing process.</li>
                        <li class="mb-2"><b>We do not alter Hostel Prices</b>: Our website includes links to third-party
                            platforms for booking accommodation. We display prices as received and do not manipulate them.
                        </li>
                        <li class="mb-2">
                            <b>Tracking Reservations</b>: Signing up on Hostelz.com unlocks exclusive features and activates
                            reservation tracking to elevate your user experience. Here’s what it involves:
                            <ul>
                                <li class="mb-2 mt-2"><strong>Automatic Activation:</strong> Tracking is automatically
                                    enabled upon sign-up, but it’s optional. Hostelz specifically tracks reservations made
                                    on partner platforms like Hostelworld.com and Booking.com when a user is actively logged
                                    into Hostelz.com, and our affiliate links are utilized.</li>

                                <li class="mb-2"><strong>Purpose:</strong> The tracked information allows us to curate
                                    personalized content such as customized guides, tailored accommodation recommendations,
                                    and essential travel resources based on your reservation details and interests.</li>
                            </ul>
                            <p class="mb-3">Your understanding and consent to this tracking are vital. We are committed to
                                ensuring clarity, safeguarding your privacy, and enhancing your Hostelz.com experience
                                through thoughtful and secure tracking practices.</p>
                        </li>
                    </ul>
                    </p>

                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Use of the Website</h2>
                    <p class="mb-3">Hostelz.com is a platform that aggregates information from various third-party
                        accommodation providers. Users can finalize bookings by being directed to external platforms. The
                        User agrees to engage with the Website's features, functionalities, and community respectfully and
                        responsibly.</p>

                    <h2 class="h3 mb-1">Organic Sorting of Listings</h2>
                    <p class="mb-3">Primarily, listings are sorted based on relevance and ratings, ensuring that users can
                        easily find hostel-typed accommodations, which are always listed higher compared to other types of
                        accommodations such as hotels or guesthouses.</p>

                    <p class="mb-3">For our signed-up members, we offer a more personalized experience. Listings may be
                        sorted based on their profile and preferences, allowing for a customized user experience that aligns
                        with individual needs and interests.</p>

                    <p class="mb-3">We uphold a high standard of integrity and impartiality in our listing process. Please
                        be assured that the sorting of listings is not influenced by commissions or any other benefits
                        received from third parties. Our main objective is to facilitate an unbiased and user-focused
                        platform, allowing you to make informed decisions regarding your accommodation choices.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">User Reviews and Comments</h2>
                    <p class="mb-3">Hostelz.com reserves the right to make minor modifications to the user reviews for the
                        purpose of formatting consistency and content appropriateness, such as correcting typos or grammar.
                        However, we will not make changes that would alter the essence or overall intention behind the
                        user’s original comment. In addition, Hostelz.com reserves the right to withhold the publication of
                        reviews based on content appropriateness and relevance.</p>

                    <p class="mb-3">We are committed to maintaining the integrity and authenticity of the user reviews on
                        our platform, ensuring that they genuinely reflect the users’ experiences and opinions.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Community Guidelines</h2>
                    <p class="mb-3">Users of Hostelz.com are expected to engage respectfully with the platform and its
                        community. The following behaviors are grounds for the termination or suspension of access to
                        Hostelz.com:</p>
                    <ul>
                        <li class="mb-2">Use of profanity, hate speech, sexually explicit content, discriminatory remarks,
                            threats, or violence.</li>
                        <li class="mb-2">Personal attacks, promoting illegal activities, or making politically sensitive
                            comments.</li>
                    </ul>
                    <p class="mb-3">Users are encouraged to report any such conduct to maintain the integrity and user
                        experience of the Hostelz.com community.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Exclusive Guides</h2>
                    <p class="mb-3">Hostelz.com offers exclusive guides, such as itineraries, packing lists, and more,
                        available to users only. Users agree not to share or disseminate this exclusive information outside
                        of the platform, respecting the proprietary nature of the content.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Electronic Communications</h2>
                    <p class="mb-3">By using the Website, users agree to receive electronic communications from
                        Hostelz.com, including newsletters, notifications, alerts, and updates necessary for seamless user
                        experience.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Tracking of Reservations</h2>
                    <p class="mb-3">
                        When a user signs up on Hostelz.com and makes a reservation through our affiliate links to a partner
                        platform, the users agrees to allow the tracking of the reservations. The partner platforms are:</p>
                    <ul>
                        <li class="mb-2">Hostelworld.com</li>
                        <li class="mb-2">Booking.com</li>
                    </ul>
                    <p class="mb-3">The purpose behind this tracking is to
                        enhance the user experience by providing tailor-made guides and resources, such as specialized
                        destination guides, travel gear suggestions, or packing lists, based on the user's reservation
                        details.</p>
                    <ul>
                        <li class="mb-2"><strong>Reservation Management:</strong> Users cannot alter, modify, or cancel
                            the reservation through Hostelz.com.</li>
                        <li class="mb-2"><strong>User Data:</strong> No user data is sold to third parties, and
                            Hostelz.com doesn’t process payment details.</li>
                    </ul>

                    <h2 class="h3 mb-1">Which Reservations Are Being Tracked?</h2>

                    <p class="mb-3">Reservations are tracked when:</p>
                    <ul>
                        <li class="mb-2">affiliate links to our partner platforms are utilized for reservations</li>
                        <li class="mb-2">the user is actively logged in</li>
                    </ul>
                    <p class="mb-3">Affiliate links contain specific IDs to record traffic sent to the advertiser’s
                        website. We will provide these links throughout Hostelz.com when user search for and compare
                        accommodation.</p>
                    <h2 class="h3 mb-1">Access to exclusive Benefits and personalized Travel Guides</h2>
                    <p class="mb-3">Only logged-in users that follow the tracking method will gain access to the exclusive
                        benefits, personalized content, and specialized guides. It’s crucial to understand that reservations
                        made without following the tracking method above will not be subject to our tracking processes.
                        Consequently, users who did not follow this method will not have access to the exclusive benefits,
                        personalized content, and specialized guides available to those who make reservations through our
                        tracked process.</p>

                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Third-Party Links and Liability</h2>

                    <p class="mb-3">Hostelz.com incorporates links to third-party websites, including but not limited to
                        Hostelworld, Booking.com, and Hostelsclub, as part of its service. These external platforms provide
                        the basis for our price comparison, and final bookings are completed on these external websites.</p>

                    <p class="mb-3">The Provider curates and compares prices from these third-party websites, ensuring
                        that Users receive accurate and unaltered price information. Hostelz.com does not manipulate or
                        change the prices; we display them precisely as received through APIs from third-party websites. Our
                        business model is commission-based, which allows the Website to be free for Users to access and use.
                    </p>

                    <p class="mb-3">Users should exercise discretion and judgment when interacting with third-party
                        websites. Hostelz.com is not responsible or liable for any issues, damages, or losses incurred due
                        to transactions or interactions with these external platforms. All such engagements are solely
                        between the User and the respective third-party website.</p>

                    <p class="mb-3">Certain content that appears on Hostelz.com comes from Amazon. This content is
                        provided ‘as is’ and is subject to change or removal at any time. Hostelz.com is a participant in
                        the Amazon Services LLC Associates Program, an affiliate program designed to provide a means for us
                        to earn fees by linking to Amazon.com and affiliated sites. As an Amazon Associate, Hostelz earns
                        from qualifying purchases.</p>

                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">Force Majeure</h2>
                    <p class="mb-3">The Provider shall, under no circumstances whatsoever, be held responsible or liable
                        for or deemed in breach of its responsibilities under these Terms and Conditions due to any event or
                        circumstance, which the occurrence and the effect of which the party affected thereby is unable to
                        prevent and avoid, including, without limitation acts of God; pandemics, government regulation,
                        curtailment of transportation facilities, strikes, lock-outs or other industrial actions or trade
                        disputes of whatever nature (whether involving employees of a party or a third party), terrorist
                        attacks, haze, sabotage, riots, civil disturbances, insurrections, national emergencies (whether in
                        fact or law), blockades, acts of war (declared or not), etc. (a “Force Majeure Event”). The Provider
                        shall give the User a written notice describing the particulars of the Force Majeure Event as soon
                        as possible.</p>
                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">Copyright and Intellectual Property Rights</h2>
                    <p class="mb-3">All content, including text, graphics, logos, and software used on the Website, is the
                        property of Hostelz.com or its content suppliers and protected by international copyright laws.</p>
                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">Limitation of liability</h2>
                    <p class="mb-3">The Provider is not liable for any direct or indirect damages arising from the use of
                        the Website, including damages from inaccuracies, errors, or omissions in content, unavailability of
                        the Website, or damages resulting from third-party websites linked from Hostelz.com.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Choice of Law and Jurisdiction</h2>
                    <p class="mb-3">These Terms and Conditions shall be governed by and construed in accordance with the
                        laws of Cyprus. Users consent to the jurisdiction of Cyprus courts for any legal actions arising
                        from the use of the Website.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Indemnification of Lawyer Costs, Out-of-Pocket expenses and Liability for Breach
                    </h2>
                    <p class="mb-3">If the User breaches these Terms and Conditions, the Provider shall be compensated by
                        the breaching party for its reasonable lawyer costs and out-of-pocket expenses which in any way
                        relate to the breach of these Terms and Conditions.</p>
                    <p class="mb-3">The User acknowledges that compliance with these Terms and Conditions is necessary to
                        protect the goodwill and other proprietary interests of the Provider and that a breach of these
                        Terms and Conditions will also give rise to irreparable and continuing injury to the Provider.</p>
                    <p class="mb-3">Therefore, the User by using the Website hereby agrees that breach of these Terms and
                        Conditions will give the right to the Provider to seek damages for any losses and damages incurred
                        as a result of breach of these Terms and Conditions and/or in connection with such violation.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Severability</h2>
                    <p class="mb-3">If any Clause, or part of a Clause, of these Terms and Conditions, is found by any
                        court or administrative body of competent jurisdiction to be illegal, invalid or unenforceable, the
                        legality, validity or enforceability of the remainder of the Clause or Paragraph which contains the
                        relevant provision shall not be affected, unless otherwise stipulated under applicable law. If the
                        remainder of the provision is not affected, the Parties shall use all reasonable endeavours to agree
                        within a reasonable time upon any lawful and reasonable variations to the Agreement which may be
                        necessary in order to achieve, to the greatest extent possible, the same effect as would have been
                        achieved by the Clause, or the part of the Clause, in question.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Updating of the Terms and Conditions</h2>
                    <p class="mb-3">The Provider reserves the right to modify these Terms and Conditions at any time.
                        Changes will be effective immediately upon posting on the Website. Users are responsible for
                        reviewing these Terms and Conditions regularly.</p>
                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">Entire Agreement</h2>
                    <p class="mb-3">These Terms and Conditions represent the entire agreement between the Provider and the
                        User, completely replacing any other previous written or verbal agreements concerning the
                        relationship of the User with the Provider.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Contact Us</h2>
                    <p class="mb-3">For questions or feedback regarding these Terms and Conditions, Users can contact us
                        through the Contact Us page on the Website.</p>
                    <hr>
                    <p class="tx-small cl-subtext mb-3">Last Updated: October 2023</p>
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
