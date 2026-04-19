    </main>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-graduation-cap me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p>Learn anything, anytime with our comprehensive educational platform.</p>
                </div>
                <div class="col-md-2">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-white-50">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>courses/" class="text-white-50">Courses</a></li>
                        <li><a href="<?php echo SITE_URL; ?>quizzes/" class="text-white-50">Mock Tests</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> support@eduplatform.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 234 567 8900</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Follow Us</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-4">
            <div class="text-center">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</small>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
</body>
</html>