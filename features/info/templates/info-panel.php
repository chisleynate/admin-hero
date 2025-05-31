<?php
// Info form (no extra <h2>, relies on the modal header)
$uid    = get_current_user_id();
$fields = [
    'name'              => [ 'label' => 'Client Name',     'meta' => 'admin_hero_info_name'              ],
    'company_url'       => [ 'label' => 'Company URL',      'meta' => 'admin_hero_info_company_url'      ],
    'email'             => [ 'label' => 'Primary Email',    'meta' => 'admin_hero_info_email'            ],
    'phone'             => [ 'label' => 'Primary Phone',    'meta' => 'admin_hero_info_phone'            ],
    'address'           => [ 'label' => 'Mailing Address',  'meta' => 'admin_hero_info_address'          ],
    'preferred_contact' => [
        'label'   => 'Preferred Contact',
        'meta'    => 'admin_hero_info_preferred_contact',
        'type'    => 'select',
        'options' => [
            ''      => '– Select –',
            'Email' => 'Email',
            'Call'  => 'Call',
            'Text'  => 'Text',
            'Chat'  => 'Chat',
            'Mail'  => 'Mail',
        ],
    ],
    // → you can add more free URL fields here, e.g.
    // 'website_url' => [ 'label'=>'Website', 'meta'=>'admin_hero_info_website_url' ],
];
?>

<form id="admin-hero-info-form">
  <fieldset>
    <legend>Client Contact Info</legend>

    <?php foreach ( $fields as $key => $f ) :
        $value    = get_user_meta( $uid, $f['meta'], true );
        $field_id = "admin-hero-info-{$key}";
    ?>
      <p class="admin-hero-info-field">
        <label for="<?php echo esc_attr( $field_id ); ?>">
          <?php echo esc_html( $f['label'] ); ?>
        </label><br/>

        <span class="admin-hero-info-input-wrap">
          <?php if ( isset( $f['type'] ) && $f['type'] === 'select' ) : ?>
            <select
              id="<?php echo esc_attr( $field_id ); ?>"
              name="<?php echo esc_attr( $key ); ?>"
              class="regular-text"
            >
              <?php foreach ( $f['options'] as $opt_value => $opt_label ) : ?>
                <option
                  value="<?php echo esc_attr( $opt_value ); ?>"
                  <?php selected( $value, $opt_value ); ?>
                ><?php echo esc_html( $opt_label ); ?></option>
              <?php endforeach; ?>
            </select>

          <?php else : ?>
            <input
              type="text"
              id="<?php echo esc_attr( $field_id ); ?>"
              name="<?php echo esc_attr( $key ); ?>"
              value="<?php echo esc_attr( $value ); ?>"
              class="regular-text"
            />
          <?php endif; ?>

          <!-- LINK button for any *_url field -->
          <?php if ( substr( $key, -4 ) === '_url' ) :
              $has = ! empty( $value );
              $href = $has ? esc_url( $value ) : '#';
              $classes = [ 'admin-hero-link-btn' ];
              if ( ! $has ) {
                  $classes[] = 'disabled';
              }
              $title = sprintf(
                  // translators: %s: the field label, e.g. “Client Name”.
                  esc_html__( 'Open %s', 'admin-hero' ),
                  esc_html( $f['label'] )
              );
          ?>
            <a 
              href="<?php echo esc_url( $href ); ?>"
              class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
              title="<?php echo esc_attr( $title ); ?>"
              <?php if ( $has ) : ?>
                target="_blank"
              <?php else : ?>
                aria-disabled="true" tabindex="-1" onclick="return false;"
              <?php endif; ?>
            >
              <i class="fas fa-external-link-alt" aria-hidden="true"></i>
            </a>
          <?php endif; ?>

          <!-- COPY button -->
          <button
            type="button"
            class="admin-hero-copy-btn"
            data-target="<?php echo esc_attr( $field_id ); ?>"
            title="Copy <?php echo esc_attr( $f['label'] ); ?>"
          ><i class="fas fa-copy"></i></button>

        </span>
      </p>
    <?php endforeach; ?>
  </fieldset>

  <?php
    // ←—— **Pro fields get injected here** via this hook
    do_action( 'admin_hero_info_extra_fields' );
  ?>

  <p class="footer-save">
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
    Say goodbye to endless digging by keeping everything from hosting credentials to project milestones in one place. With AdminHero Pro, your most critical site details are always at your finger click. Never hunt for info again!<br>GO PRO TODAY >
  </a>
</div>
<?php endif; ?>

<script>
(function(){
  const btnSave = document.getElementById('admin-hero-info-save');
  const overlay = document.getElementById('admin-hero-overlay-info')
                || document.getElementById('admin-hero-overlay');

  // helper to toggle all URL-link buttons
  function updateUrlLinks(){
    document.querySelectorAll('input[id$="_url"]').forEach(input => {
      const wrap    = input.closest('.admin-hero-info-field');
      const linkBtn = wrap?.querySelector('.admin-hero-link-btn');
      const val     = input.value.trim();
      if (!linkBtn) return;

      if (val) {
        const href = /^([a-z][a-z0-9+.-]*:)?\/\//i.test(val)
                   ? val
                   : 'https://' + val;
        linkBtn.href = href;
        linkBtn.classList.remove('disabled');
        linkBtn.setAttribute('target','_blank');
        linkBtn.removeAttribute('aria-disabled');
        linkBtn.removeAttribute('tabindex');
        linkBtn.removeAttribute('onclick');
      } else {
        linkBtn.href = '#';
        linkBtn.classList.add('disabled');
        linkBtn.setAttribute('aria-disabled','true');
        linkBtn.setAttribute('tabindex','-1');
        linkBtn.setAttribute('onclick','return false;');
        linkBtn.removeAttribute('target');
      }
    });
  }

  btnSave.addEventListener('click', async function(){
    this.disabled = true;
    if ( overlay ) {
      overlay.style.display = 'flex';
      void overlay.offsetWidth;
      overlay.classList.add('admin-hero-visible');
    }

    const form = document.getElementById('admin-hero-info-form');
    const data = new FormData(form);
    data.set('action', 'admin_hero_save_info');
    data.set('nonce', '<?php echo esc_js( wp_create_nonce( "admin_hero_action" ) ); ?>');

    try {
      const res = await fetch(
        '<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>',
        {
          method: 'POST',
          body: data
        }
      );
      const json = await res.json();
      if ( ! json.success ) {
        console.error('Info save failed:', json);
      }
    } catch (err) {
      console.error('Network error:', err);
    } finally {
      if ( overlay ) {
        setTimeout(()=>{
          overlay.classList.remove('admin-hero-visible');
          setTimeout(()=> overlay.style.display = 'none', 200);
        }, 1000);
      }
      this.disabled = false;

      // update links immediately
      updateUrlLinks();

      // notify any Pro listeners (if needed)
      document.dispatchEvent(new CustomEvent('admin-hero-saved'));
    }
  });

  // COPY‐TO‐CLIPBOARD handler
  document.querySelectorAll('.admin-hero-copy-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const inputEl = document.getElementById(btn.dataset.target);
      if (!inputEl) return;
      try {
        await navigator.clipboard.writeText(inputEl.value);
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1000);
      } catch (err) {
        console.error('Copy failed:', err);
      }
    });
  });

  // also respond to the custom event for Pro sections
  document.addEventListener('admin-hero-saved', updateUrlLinks);

  // Initialize URL‐links once on load
  updateUrlLinks();
})();
</script>