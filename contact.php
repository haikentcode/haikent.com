<section id="contact" class="contact">
  <div class="container">

    <div class="section-title">
      <h2>Contact</h2>
      <p>Contact Me</p>
    </div>

    <div class="row mt-2">

      <div class="col-md-6 d-flex align-items-stretch">
        <div class="info-box">
          <i class="bx bx-map"></i>
          <h3>My Address</h3>
          <p>Sector 27 , Gurgaon</p>
        </div>
      </div>

      <div class="col-md-6 mt-4 mt-md-0 d-flex align-items-stretch">
        <div class="info-box">
          <i class="bx bx-share-alt"></i>
          <h3>Social Profiles</h3>
          <div class="social-links">
            <?php include("social-links.php") ?>
          </div>
        </div>
      </div>

      <div class="col-md-6 mt-4 d-flex align-items-stretch">
        <div class="info-box">
          <i class="bx bx-envelope"></i>
          <h3>Email Me</h3>
          <p>haikenthk@gmail.com</p>
        </div>
      </div>
      <div class="col-md-6 mt-4 d-flex align-items-stretch">
        <div class="info-box">
          <i class="bx bx-phone-call"></i>
          <h3>Call Me</h3>
          <p>+91-82090568**</p>
        </div>
      </div>
    </div>

    <form action="/contact.php" method="post" role="form" class="php-email-form mt-4">
      <div class="form-row">
        <div class="col-md-6 form-group">
          <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" data-rule="minlen:4" data-msg="Please enter at least 4 chars" />
          <div class="validate"></div>
        </div>
        <div class="col-md-6 form-group">
          <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" data-rule="email" data-msg="Please enter a valid email" />
          <div class="validate"></div>
        </div>
      </div>
      <div class="form-group">
        <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" data-rule="minlen:4" data-msg="Please enter at least 8 chars of subject" />
        <div class="validate"></div>
      </div>
      <div class="form-group">
        <textarea class="form-control" name="message" rows="5" data-rule="required" data-msg="Please write something for us" placeholder="Message"></textarea>
        <div class="validate"></div>
      </div>
      <div class="mb-3">
        <div class="loading">Loading</div>
        <div class="error-message"></div>
        <div class="sent-message">Your message has been sent. Thank you!</div>
      </div>
      <div class="text-center"><button type="submit">Send Message</button></div>
    </form>

  </div>
</section>
