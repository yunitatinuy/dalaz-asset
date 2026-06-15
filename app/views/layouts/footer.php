</div>
</main>

<script src="<?= BASE_URL ?>/public/js/main.js"></script>
<script src="<?= BASE_URL ?>/public/js/helpers.js"></script>

<?php if (isset($data['pageJS'])): ?>
    <script src="<?= BASE_URL ?>/public/js/<?= $data['pageJS'] ?>.js" data-base-url="<?= BASE_URL ?>/public/index.php?url="></script>
<?php endif; ?>

</body>

</html>