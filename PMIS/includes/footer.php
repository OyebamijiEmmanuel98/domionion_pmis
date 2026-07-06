<?php
/**
 * =====================================================
 * DOMINION UNIVERSITY, IBADAN
 * PERSONNEL MANAGEMENT INFORMATION SYSTEM (PMIS)
 * FOOTER INCLUDE FILE
 * =====================================================
 * 
 * This file contains the footer and closing HTML tags.
 * Include this at the end of every page.
 * 
 * @author Final Year Project
 * @version 1.0
 */
?>
    </div><!-- End Content -->
    
    <!-- Footer -->
    <footer class="footer" style="padding: var(--spacing-lg); text-align: center; color: var(--text-muted); font-size: var(--font-size-sm); border-top: 1px solid var(--border-color); margin-top: var(--spacing-xl);">
        <p>&copy; <?php echo date('Y'); ?> Dominion University, Ibadan. All Rights Reserved.</p>
        <p>Personnel Management Information System (PMIS) - Final Year Project</p>
    </footer>
    
</main><!-- End Main Content -->
</div><!-- End App Container -->

<!-- Main JavaScript -->
<script src="<?php echo getBaseUrl(); ?>assets/js/main.js"></script>

<!-- Page-specific JavaScript -->
<?php if (isset($pageScripts)): ?>
<script><?php echo $pageScripts; ?></script>
<?php endif; ?>

<!-- Custom scripts for this page -->
<?php if (isset($customJS)): ?>
<script src="<?php echo getBaseUrl() . $customJS; ?>"></script>
<?php endif; ?>

</body>
</html>
