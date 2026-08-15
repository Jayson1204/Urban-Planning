  </div>

  <?php $dashboardJsPath = __DIR__ . '/../assets/js/dashboard.js'; $dashboardJsVer = @filemtime($dashboardJsPath) ?: time(); ?>
  <script src="<?php echo $basePath ?? '../'; ?>assets/js/dashboard.js?v=<?php echo $dashboardJsVer; ?>"></script>
</body>
</html>