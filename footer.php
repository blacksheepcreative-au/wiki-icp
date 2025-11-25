<?php $brand = get_brand_data(); ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <p>&copy; <?php echo date('Y'); ?> <a href="<?php echo esc_url($brand['website']); ?>"><?php echo esc_html($brand['name']); ?></a>. All rights reserved.</p>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
