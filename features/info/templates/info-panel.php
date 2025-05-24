<?php
// Info form (no extra <h2>, relies on the modal header)
$uid = get_current_user_id();
$fields = [
    'name'    => [ 'label' => 'Client Name',   'meta' => 'admin_hero_info_name'    ],
    'company' => [ 'label' => 'Company',       'meta' => 'admin_hero_info_company' ],
    'email'   => [ 'label' => 'Primary Email', 'meta' => 'admin_hero_info_email'   ],
    'phone'   => [ 'label' => 'Primary Phone', 'meta' => 'admin_hero_info_phone'   ],
];
?>

<form id="admin-hero-info-form">
    <?php foreach ( $fields as $key => $f ) :
        $value = get_user_meta( $uid, $f['meta'], true );
    ?>
        <p>
            <label for="admin-hero-info-<?php echo esc_attr( $key ); ?>">
                <?php echo esc_html( $f['label'] ); ?>
            </label><br/>
            <input
                type="text"
                id="admin-hero-info-<?php echo esc_attr( $key ); ?>"
                name="<?php echo esc_attr( $key ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                class="regular-text"
            />
        </p>
    <?php endforeach; ?>

    <p>
        <button type="button" id="admin-hero-info-save" class="admin-hero-button">
            Update
        </button>
    </p>
</form>

<?php if ( ! function_exists('admin_hero_pro_license_valid') || ! admin_hero_pro_license_valid() ) : ?>
<div class="admin-hero-info-pro">
  <hr>
  <a href="https://adminhero.pro" target="_blank"
      class="admin-hero-button button-pro">
    Get Pro today for EXTENDED INFO!
  </a>
</div>
<?php endif; ?>

<script>
(function(){
  const btn     = document.getElementById('admin-hero-info-save');
  const overlay = document.getElementById('admin-hero-overlay-info');
  const span    = overlay.querySelector('span');

  btn.addEventListener('click', async function(){
    this.disabled = true;

    // 1) Show overlay immediately
    overlay.style.display = 'flex';
    void overlay.offsetWidth;
    overlay.classList.add('admin-hero-visible');

    // 2) Do AJAX save
    const form = document.getElementById('admin-hero-info-form');
    const data = new FormData(form);
    data.set('action', 'admin_hero_save_info');
    data.set('nonce', '<?php echo wp_create_nonce("admin_hero_action"); ?>');
    <?php foreach ( $fields as $key => $_ ) : ?>
    data.set('<?php echo esc_js($key); ?>',
      form.querySelector('[name="<?php echo esc_js($key); ?>"]').value
    );
    <?php endforeach; ?>

    try {
      const res  = await fetch('<?php echo esc_js(admin_url("admin-ajax.php")); ?>',{
        method:'POST', body:data
      });
      const json = await res.json();
      if ( ! json.success ) {
        console.error('Info save failed:', json);
      }
    } catch (err) {
      console.error('Network error:', err);
    } finally {
      // 3) Hide overlay after 1s, then remove after fade (0.2s)
      setTimeout(() => {
        overlay.classList.remove('admin-hero-visible');
        setTimeout(() => overlay.style.display = 'none', 200);
      }, 1000);
      this.disabled = false;
    }
  });
})();
</script>
