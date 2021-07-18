	
	<script>
	PHP_VARIABLES = <?php echo isset($php_variables) ? $php_variables : '[]'; ?>;
	</script>
	
	<?php if (isset($js_links)): ?>
	<?php foreach ($js_links as $js_link): ?>
	<script src="<?php echo $js_link; ?>"></script>
	<?php endforeach; ?>
	<?php endif; ?>
</body>
</html>