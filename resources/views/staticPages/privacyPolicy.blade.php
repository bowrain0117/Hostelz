<?php
Lib\HttpAsset::requireAsset('booking-main.js');
?>

@extends('layouts/default', ['showHeaderSearch' => true])

@section('title', 'Privacy Policy - Hostelz.com')

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
                    {!! breadcrumb('Privacy Policy') !!}
                </ul>



                <div class="mb-4">
                    <h1 class="mb-lg-5 pb-md-2 mb-3">Privacy Policy</h1>
                    <p class="mb-3">This privacy policy ("policy") will help you understand how Hostelz (hereinafter
                        referred to as "us", "we", "our") process the data you supply to us when you access as well as use
                        https://www.hostelz.com (hereinafter referred to as "website", "service").</p>
                    <p class="mb-3">We have the absolute, complete and full discretion to modify or adjust this policy in
                        any shape or form at any moment in the future, and you will be promptly updated. If you desire to
                        ensure that you are updated with all of the latest changes, we would suggest and recommend that you
                        periodically or consistently visit our privacy policy.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">What User Data We Process</h2>
                    <p class="mb-3">When you use our website, we may collect and process the following data:</p>
                    <ul>
                        <li class="mb-2">Your IP address</li>
                        <li class="mb-2">Your contact details such as name, location, email address for signing up and
                            logging in, among
                            other similar things</li>
                        <li class="mb-2">Other information such as interests, which other websites you visited through our
                            website and
                            preferences</li>
                        <li class="mb-2">Relevant data concerning your online behaviour on the way you use our website
                        </li>
                    </ul>
                    <p class="mb-3">The website servers may collect the IP address of your computer in order to use this
                        information to measure the number of visits, average time spent at the Website, pages viewed, clicks
                        to other websites or just generally to better understand the technical issues you are facing with
                        either your software or hardware etc. We use this information to determine use of the website, and
                        to improve its content.</p>
                    <p class="mb-3">Please note that we will never ask or collect any passwords you may have in connection
                        to our website. Users need to set their own password to register.</p>
                    <p class="mb-3">We request you to provide us with accurate and complete information when logging in
                        and signing up on our website. This obligation is a condition to the provision of our services to
                        you and/or any other person authorized or permitted by you or your organization/company to use the
                        services.</p>
                    <p class="mb-3">Sign In users can create own public profile pages. These pages are in control of the
                        signed up users and accesible by internet users outside of Hostelz.com. The Email Address of the
                        users will never be shown publicly.</p>
                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">The Reasons We Collect And Process Your Data</h2>
                    <p class="mb-3">We are processing, using and collecting your data, through various measures such as
                        Google Analytics, for the following purposes and reasons:</p>
                    <ul>
                        <li class="mb-2">To more accurately comprehend your needs.</li>
                        <li class="mb-2">To enhance and make our services and products substantially better.</li>
                        <li class="mb-2">To send you promotional emails with the relevant details that we believe you will
                            think are
                            intriguing.</li>
                        <li class="mb-2">To let you know that from time to time we would like for you to submit optional
                            surveys for us
                            to conduct some market research.</li>
                        <li class="mb-2">To personalise our website with regard to your online behavior depending on what
                            you would enjoy
                            more of.</li>
                        <li class="mb-2">For potential marketing or advertising rationales.</li>
                    </ul>
                </div>


                <div class="mb-4">
                    <h2 class="h3 mb-1">Tracking of Reservations</h2>
                    <p class="mb-3">Hostelz.com employs tracking technologies like affiliate links to improve user
                        experience and service quality. Below are the key aspects of our tracking practices:</p>
                    <ul>
                        <li class="mb-2">Technologies Used: Primarily facilitated through affiliate links.</li>
                        <li class="mb-2">Types of Data Tracked: Includes details such as booking status, name of accommodation, destination, number of people, nationality, booking time, start and end date of reservation, language, deposit being paid, user device (desktop, mobile, or app)</li>
                        <li class="mb-2">User Control: Users have control over their data, with the option to delete their
                            accounts and manage tracking preferences.</li>
                    </ul>
                   
                    <p class="mb-3">Our tracking practices are designed with user privacy as a priority, ensuring that
                        users have clear insights and control over the tracking activities pertinent to their interaction
                        with Hostelz.com.</p>
                    <h2 class="h3 mb-1">Account Deletion</h2>
                    <p class="mb-3">Users have the right to request the deletion of their account. To do so, users should
                        get in touch with Hostelz.com directly, and necessary actions will be taken to process the deletion
                        of the user’s account and related data.</p>
                </div>


                <div class="mb-4">
                    <h2 class="h3 mb-1">Safeguarding and Securing the Data</h2>
                    <p class="mb-3">Hostelz is committed to securing your data and keeping it confidential. We are taking
                        data theft very seriously and we are taking substantial measures to prevent data theft or anything
                        else that infringes and violates your personal data and information.</p>
                    <p class="mb-3">Any of your personal information that may result from the use of the website will be
                        kept by the Provider, in accordance with relevant regulations and could be used for advertising
                        purposes. For potential users in European nations and in accordance with the GDPR, which is an EU
                        regulation, we will ensure that the use of your personal data is fair, lawful, accurate, not
                        excessive and not used for longer than necessary. Additionally, you are hereby informed that we will
                        use and process your personal data.</p>
                </div>

                <div class="mb-4">
                    <h2 class="h3 mb-1">Using and Processing of Cookies</h2>
                    <p class="mb-3">Once you agree to allow our website to use cookies, you also acknowledge as well as
                        agree to use the data it processes concerning your general actions (for instance, analyze web
                        traffic, web pages you spend the most time on, and websites you visit).</p>
                    <p class="mb-3">The definition of a cookie is that it is a minimal amount of information that is
                        downloaded or installed to your PC or device at the time of using, accessing or visiting this
                        website. We process and utilise several cookies of various types, including operational, marketing
                        and/or content cookies.</p>
                    <p class="mb-3">The data we collect and receive by using and processing cookies is utilized to
                        customize and personalise this website to your specific and exact needs. Therefore, cookies make
                        your browsing experience better by allowing the website to remember your actions and preferences
                        (such as login and region selection). This then means and therefore, indicates you do not have to
                        insert the same information every single time you decide to use or visit our website. Additionally,
                        cookies generally also have the capability to supply relevant information regarding your online
                        actions and behavior, for example the number of times you have visited our website. When we are
                        finished with the analysis of this data that the cookies have supplied us for the purposes of
                        understanding certain statistical information, the data will no longer be part of any of our
                        systems.</p>
                    <p class="mb-3">Please be aware that cookies do not enable us to obtain control of your PC in any way.
                        They are strictly used to monitor and check which pages you find useful or suitable or appropriate
                        and which yu do not so that we can supply you with a more enhanced experience.</p>
                    <p class="mb-3">If you desire not to allow us to collect any of the cookies derived from your PC, you
                        are free to do it via the settings of the browser you are using with your PC.</p>
                    <p class="mb-3">Similarly, we follow a standard procedure of using log files. These files log visitors
                        when they visit websites. All hosting companies are acting in a similar manner and constitutes a
                        part of hosting services' analytics. As aforementioned with the data we collect, the information
                        collected by log files can also include internet protocol (IP) addresses, browser type, Internet
                        Service Provider (ISP), date and time stamp, referring/exit pages, and possibly the number of
                        clicks. These are not linked to any information that is personally identifiable. The purpose of the
                        information is for analyzing trends, administering the site, tracking users' movement on the
                        website, and gathering demographic information.</p>
                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">Restricting the Collection of your Personal Data</h2>
                    <p class="mb-3">In the event that you might wish to restrict the use and collection of your personal
                        data. You can do this by acting in the following way:</p>
                    <ul>
                        <li class="mb-2">When you are filling the forms on the website, ensure to check whether there is a
                            box which you can leave unchecked, if you do not wish to provide us with your personal data.
                        </li>
                        <li class="mb-2">If you have already agreed to share your information with us, you can always
                            email us and we will certainly allow you to make the relevant adjustments you would like.</li>
                    </ul>
                    <p class="mb-3">Hostelz will not sell or distribute your personal information to any external third
                        parties, if we do not have consent. We may act this way if it is instructed by any laws or
                        regulations. Your personal information and data will be utilised in order to advertise certain
                        relevant material to you, for example in your email address, subject to you expressly agreeing to
                        this privacy policy. This is in line with the California Online Privacy Protection Act.</p>
                    <p class="mb-3">Furthermore, we are in compliance with the requirements of COPPA (Childrens Online
                        Privacy Protection Act). We do not collect any information from anyone under 13 years of age. Our
                        website, products, and services are all directed to people who are at least 13 years old or older.
                    </p>
                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">Your Consent</h2>
                    <p class="mb-3">By using our site you consent to our privacy policy.</p>
                    <p class="mb-3">If we decide to change our privacy policy we will post those changes on this page.</p>
                </div>
                <div class="mb-4">
                    <h2 class="h3 mb-1">Contact Us</h2>
                    <p class="mb-3">If you have any questions or want to provide feedback regarding our privacy policy,
                        you can always contact us through the Contact Us page on the website.</p>
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
