// Wait until the page is fully loaded
$(document).ready(function() {
    // Attach submit event handler to the login form
    $('#login-form').on('submit', function(e) {
        // Clear any previous error messages
        $('#email-error').text('');
        $('#password-error').text('');

        // Get values from input fields
        let email = $('#email').val().trim();
        let password = $('#password').val().trim();
        let hasError = false; // Flag to check if there are validation errors

        // Check if email is empty
        if (!email) {
            $('#email-error').text('Email is required.');
            hasError = true;
        }
        // Check if email format is invalid
        else if (!validateEmail(email)) {
            $('#email-error').text('Invalid email format.');
            hasError = true;
        }
        // Check if password is empty
        if (!password) {
            $('#password-error').text('Password is required.');
            hasError = true;
        }

        // If there are any errors, prevent the form from being submitted
        if (hasError) {
            e.preventDefault();
        }
    });

    // Helper function to validate correct email format using regular expression
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});

/*
 * <?php require('template/header.phtml') ?>
   <!-- HOME SLIDER -->
     <section id="home">
          <div class="row">
               <div class="owl-carousel owl-theme home-slider">
                    <div class="item item-first">
                       <div class="caption">
    <div class="bg-overlay"></div> 
    <div class="container">
        <div class="col-md-6 col-sm-12">
            <h1>Share Your EV Charger</h1>
            <h3>Earn money by renting out your home charging point when you're not using it.</h3>
            <a href="register.php" class="section-btn btn btn-default">Register Now</a>
        </div>
    </div>
</div>

                    </div>

                    <div class="item item-second">
                         <div class="caption">
                              <div class="container">
                                   <div class="col-md-6 col-sm-12">
                                        <h1>Need a Charge? Find a nearby charger.</h1>
                                        <h3>Book affordable charging points in your neighborhood with just a few clicks.</h3>
                                        <a href="search.php" class="section-btn btn btn-default">Find Chargers</a>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <div class="item item-third">
                         <div class="caption">
                              <div class="container">
                                   <div class="col-md-6 col-sm-12">
                                        <h1>Community Powered Charging</h1>
                                        <h3>Join thousands of EV owners sharing charging resources and making electric vehicle ownership easier for everyone.</h3>
                                        <a href="about.php" class="section-btn btn btn-default">Learn More</a>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </div>
     </section>

     <main>
          <section>
               <div class="container">
                    <div class="row">
                         <div class="col-md-12 col-sm-12">
                              <div class="text-center">
                                   <h2>About Borrow My Charger</h2>

                                   <br>

                                   <p class="lead">Borrow My Charger connects EV owners with local charging solutions. Our platform enables homeowners to monetize their charging points during downtime, while providing flexible charging options for EV drivers. With an easy-to-use booking system and interactive maps, finding and reserving a charging point has never been simpler. Join our growing community today and be part of the sustainable transportation revolution.</p>
                              </div>
                         </div>
                    </div>
               </div>
          </section>

          <section>
               <div class="container">
                    <div class="row">
                         <div class="col-md-12 col-sm-12">
                              <div class="section-title text-center">
                                   <h2>How It Works <small>Simple steps to start charging or sharing</small></h2>
                              </div>
                         </div>

                         <div class="col-md-4 col-sm-6">
                              <div class="team-thumb">
                                   <div class="team-image">
                                        <img src="images/homepage/home-charger-6.jpg" class="img-responsive" alt="Registration">
                                   </div>
                                   <div class="team-info">
                                        <h3>REGISTER</h3>

                                        <p class="lead">Simple and <strong>free</strong> to join</p>

                                        <span>Create an account as a homeowner to list your charger or as a user to find and book charging points. Our secure sign-up process takes less than 2 minutes.</span>
                                   </div>
                                   <div class="team-thumb-actions">
                                        <a href="register.php" class="section-btn btn btn-primary btn-block">Sign Up Now</a>
                                   </div>
                              </div>
                         </div>

                         <div class="col-md-4 col-sm-6">
                             <div class="team-thumb">
                                   <div class="team-image">
                                        <img src="images/homepage/home-charger-5.jpg" class="img-responsive" alt="Book Charger">
                                   </div>
                                   <div class="team-info">
                                        <h3>BOOK & CHARGE</h3>

                                        <p class="lead">Simple <strong>booking</strong> process</p>

                                        <span>Send a request to book a charging point for your preferred time. Once approved by the homeowner, just show up and charge your vehicle. It's that simple!</span>
                                   </div>
                                   <div class="team-thumb-actions">
                                        <a href="browse-charger.php" class="section-btn btn btn-primary btn-block">Learn More</a>
                                   </div>
                              </div>
                         </div>

                       <div class="col-md-4 col-sm-6">
    <div class="team-thumb">
        <div class="team-image">
            <img src="images/homepage/home-charger-6.jpg" class="img-responsive" alt="Frequently Asked Questions">
        </div>
        <div class="team-info">
            <h3>Frequently Asked Questions</h3>
            <p class="lead">Got questions? We have answers!</p>
            <span>Find the most common questions about our service and how to get started.</span>
        </div>
        <div class="team-thumb-actions">
            <a href="#faq" class="section-btn btn btn-primary btn-block">Learn More</a>
        </div>
    </div>
</div>
                    </div>
               </div>
          </section>

          <section>
               <div class="container">
                    <div class="row">
                         <div class="col-md-12 col-sm-12">
                              <div class="section-title text-center">
                                   <h2>Featured Charging Points <small>Popular locations near you</small></h2>
                              </div>
                         </div>

                         <!-- Featured charging points with equal height images -->
                         <div class="col-md-4 col-sm-4">
                              <div class="courses-thumb courses-thumb-secondary">
                                   <div class="courses-top">
                                        <div class="courses-image" style="height: 220px; overflow: hidden;">
                                             <img src="images/homepage/home-charger-1.jpg" class="img-responsive" alt="Home charger" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="courses-date">
                                             <span title="Owner"><i class="fa fa-user"></i> Sarah M.</span>
                                             <span title="Location"><i class="fa fa-map-marker"></i> Manchester</span>
                                             <span title="Type"><i class="fa fa-plug"></i> 7kW</span>
                                        </div>
                                   </div>

                                   <div class="courses-detail">
                                        <h3><a href="charger-details.php?id=1">Fast Charging in Quiet Neighborhood - Easy Access</a></h3>
                                   </div>

                                   <div class="courses-info">
                                        <a href="charger-details.php?id=1" class="section-btn btn btn-primary btn-block">View Details</a>
                                   </div>
                              </div>
                         </div>

                         <div class="col-md-4 col-sm-4">
                              <div class="courses-thumb courses-thumb-secondary">
                                   <div class="courses-top">
                                        <div class="courses-image" style="height: 220px; overflow: hidden;">
                                             <img src="images/homepage/home-charger-2.jpg" class="img-responsive" alt="Home charger" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="courses-date">
                                             <span title="Owner"><i class="fa fa-user"></i> James P.</span>
                                             <span title="Location"><i class="fa fa-map-marker"></i> Leeds</span>
                                             <span title="Type"><i class="fa fa-plug"></i> 11kW</span>
                                        </div>
                                   </div>

                                   <div class="courses-detail">
                                        <h3><a href="charger-details.php?id=2">City Center Charging - Perfect for Shopping Trips</a></h3>
                                   </div>

                                   <div class="courses-info">
                                        <a href="charger-details.php?id=2" class="section-btn btn btn-primary btn-block">View Details</a>
                                   </div>
                              </div>
                         </div>

                         <div class="col-md-4 col-sm-4">
                              <div class="courses-thumb courses-thumb-secondary">
                                   <div class="courses-top">
                                        <div class="courses-image" style="height: 220px; overflow: hidden;">
                                             <img src="images/homepage/home-charger-3.jpg" class="img-responsive" alt="Home charger" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="courses-date">
                                             <span title="Owner"><i class="fa fa-user"></i> Emma T.</span>
                                             <span title="Location"><i class="fa fa-map-marker"></i> Birmingham</span>
                                             <span title="Type"><i class="fa fa-plug"></i> 22kW</span>
                                        </div>
                                   </div>

                                   <div class="courses-detail">
                                        <h3><a href="charger-details.php?id=3">Premium Rapid Charger - Available 24/7</a></h3>
                                   </div>

                                   <div class="courses-info">
                                        <a href="charger-details.php?id=3" class="section-btn btn btn-primary btn-block">View Details</a>
                                   </div>
                              </div>
                         </div>
                    </div>
               </div>
          </section>

          <!-- TESTIMONIALS -->
          <section id="testimonial">
               <div class="container">
                    <div class="row">
                         <div class="col-md-12 col-sm-12">
                              <div class="section-title text-center">
                                   <h2>Testimonials <small>from our community</small></h2>
                              </div>

                              <div class="owl-carousel owl-theme owl-client">
                                   <div class="col-md-4 col-sm-4">
                                        <div class="item">
                                             <div class="tst-image">
                                                  <img src="images/homepage/testimonial-img-1.jpg" class="img-responsive" alt="User testimonial">
                                             </div>
                                             <div class="tst-author">
                                                  <h4>Michael</h4>
                                                  <span>Tesla Model 3 Owner</span>
                                             </div>
                                             <p>"Finding a charging point used to be my biggest headache as an EV owner. With Borrow My Charger, I can now plan my trips without worry. I've used it 5 times already and had great experiences every time!"</p>
                                             <div class="tst-rating">
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                             </div>
                                        </div>
                                   </div>

                                   <div class="col-md-4 col-sm-4">
                                        <div class="item">
                                             <div class="tst-image">
                                                  <img src="images/homepage/testimonial-img-2.jpg" class="img-responsive" alt="User testimonial">
                                             </div>
                                             <div class="tst-author">
                                                  <h4>Jennifer</h4>
                                                  <span>Homeowner</span>
                                             </div>
                                             <p>"I was hesitant at first, but listing my home charger has been fantastic. I've made over £200 in just three months by sharing my charger when I'm at work. The platform is easy to use and the booking system works flawlessly."</p>
                                             <div class="tst-rating">
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star-half-o"></i>
                                             </div>
                                        </div>
                                   </div>

                                   <div class="col-md-4 col-sm-4">
                                        <div class="item">
                                             <div class="tst-image">
                                                  <img src="images/homepage/testimonial-img-3.jpg" class="img-responsive" alt="User testimonial">
                                             </div>
                                             <div class="tst-author">
                                                  <h4>David</h4>
                                                  <span>Nissan Leaf Owner</span>
                                             </div>
                                             <p>"I live in an apartment with no charging facilities. Borrow My Charger has been a game-changer for me. I've found a regular charging spot just 5 minutes from my home. The homeowner is lovely and the price is much better than public chargers."</p>
                                             <div class="tst-rating">
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                                  <i class="fa fa-star"></i>
                                             </div>
                                        </div>
                                   </div>
                              </div>
                        </div>
                    </div>
               </div>
          </section>
          
         <!-- MAP PREVIEW -->
<section id="map-preview" style="background-color: #E8E9EF; padding: 60px 0;">
     <div class="container">
          <div class="row">
               <div class="col-md-12 text-center mb-4">
                    <h2>Find Charging Points Near You</h2>
                    <p class="lead">Our interactive map makes it easy to locate available chargers in your area</p>
               </div>
               <div class="col-md-12">
                    <div id="charger-map" style="height: 400px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);"></div>
                  
               </div>
          </div>
     </div>
</section>

     </main>
   
   <!-- FAQ Section -->
<section id="faq" class="faq-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="lead">Everything you need to know about Borrow My Charger</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="faq-accordion">
                    <!-- FAQ Item 1 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>How does Borrow My Charger work?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>Borrow My Charger connects EV owners with homeowners who have charging points. Homeowners can list their charging points, set availability and prices, while EV owners can search, book, and use these charging points for a fee.</p>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 2 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>How do I register as a homeowner?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>Simply click on the "Register" button, fill out the registration form, and select "Homeowner" as your account type. Once registered, you'll be able to add your charging point details including location, availability, and price per kWh.</p>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 3 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>How do I find a charging point near me?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>After logging in as a user, you can use our interactive map to locate charging points near your current location. You can also filter results based on price, availability, and distance.</p>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 4 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>How much does it cost to use a charging point?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>Prices are set by each homeowner and typically range from £0.20 to £0.40 per kWh. You can see the exact price before booking a charging point.</p>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 5 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>How do I book a charging point?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>Once you've found a suitable charging point, you can send a booking request for your preferred date and time. The homeowner will then approve or decline your request, and you'll receive a notification.</p>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 6 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>How do I get paid as a homeowner?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>When a user books your charging point, they pay through our secure system. After the charging session is complete, the payment is transferred to your account, minus a small service fee.</p>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 7 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>Is my personal information secure?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, we take data security seriously. All personal information is encrypted and stored securely. We implement strict security measures to prevent unauthorized access to your data.</p>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 8 -->
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <h3>What if there's a problem during a charging session?</h3>
                            <span class="faq-icon">▼</span>
                        </div>
                        <div class="faq-answer">
                            <p>If you encounter any issues during a charging session, you can contact the homeowner directly through our messaging system. If the issue persists, our customer support team is available to help resolve any problems.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


  <section id="contact" style="background-color: #B68942; color: white;">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <form id="contact-form" role="form" method="post">
                    <div class="section-title">
                        <h2 style="color: white;">Contact us <small style="color: #E8E9EF;">Have more questions? We're here to help!</small></h2>
                    </div>
                    <div class="col-md-12 col-sm-12">
                        <input type="text" class="form-control" placeholder="Enter full name" name="name" required>
                        <span id="name-error" style="color: #FFEB3B; font-size: 14px; display: none;"></span>

                        <input type="email" class="form-control" placeholder="Enter email address" name="email" required>
                        <span id="email-error" style="color: #FFEB3B; font-size: 14px; display: none;"></span>

                        <textarea class="form-control" rows="6" placeholder="Tell us your question or concern" name="message" required></textarea>
                        <span id="message-error" style="color: #FFEB3B; font-size: 14px; display: none;"></span>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <input type="submit" class="form-control" value="Send Message" style="background-color: #868942; color: white; border: none;">
                    </div>
                </form>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="contact-image">
                    <img src="images/homepage/home-charger-5.jpg" class="img-responsive" alt="EV Charging" style="border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Improved Modal for success/error messages -->
<div id="messageModal" class="modal">
    <div class="modal-content" style="border-radius: 8px; max-width: 500px; padding: 30px;">
        <span class="close" id="closeModal">&times;</span>
        <div id="modalMessage" class="text-center"></div>
        <div class="text-center" style="margin-top: 20px;">
            <button id="closeModalBtn" class="btn" style="background-color: #868942; color: white; border: none; padding: 8px 20px; border-radius: 4px;">Close</button>
        </div>
    </div>
</div>


     <!-- CTA SECTION -->
     <section id="cta" >
          <div class="container">
               <div class="row">
                    <div class="col-md-8 col-sm-12">
                         <h2>Ready to join our charging community?</h2>
                         <p class="lead">Start sharing your charger or find convenient charging spots today.</p>
                    </div>
                    <div class="col-md-4 col-sm-12 text-center" style="padding-top: 10px;">
                         <a href="register.php" class="btn btn-lg" style="background-color: #868942; color: white; border: none;">Sign Up Now</a>
                    </div>
               </div>
          </div>
     </section>
     
<script src="js/contactForm.js"></script>
<script src="js/map.js"></script>
<script src="js/faq.js"></script>

<?php require('template/footer.phtml') ?>
 */