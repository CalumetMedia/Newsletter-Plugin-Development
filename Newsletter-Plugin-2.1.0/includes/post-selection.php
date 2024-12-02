<?php
// Ensure this file is part of the plugin
if (!defined('ABSPATH')) exit;

function cnp_newsletter_editor_page($newsletter_id) {
    if (isset($_POST['filter_posts'])) {
        $_SESSION["strStartDate"] = sanitize_text_field($_POST["strStartDate"]);
        $_SESSION["strEndDate"] = sanitize_text_field($_POST["strEndDate"]);
        $_SESSION["stories"] = isset($_POST["stories"]) ? array_map('sanitize_text_field', $_POST["stories"]) : [];
    }

    $start_date = $_SESSION["strStartDate"] ?? date("Y-m-d", strtotime("-7 days"));
    $end_date = $_SESSION["strEndDate"] ?? date("Y-m-d");

    $args = array(
        'date_query' => array(
            array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ),
        ),
        'posts_per_page' => -1,
        'category__in' => cnp_get_newsletter_categories($newsletter_id),
    );

    $query = new WP_Query($args);
    ?>
    <div class="wrap">
        <h1><?php echo get_option("newsletter_{$newsletter_id}_name"); ?> - Post Selection</h1>
        <form method="post">
            <label>Start Date:</label>
            <input type="date" name="strStartDate" value="<?php echo esc_attr($start_date); ?>">
            <label>End Date:</label>
            <input type="date" name="strEndDate" value="<?php echo esc_attr($end_date); ?>">
            <p><input type="submit" name="filter_posts" value="Filter Posts" class="button-primary"></p>

            <h3>Manual Post Selection</h3>
            <p><strong>Select Posts for Newsletter:</strong></p>
            <div id="chk">
                <?php if ($query->have_posts()): ?>
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <label>
                            <input type="checkbox" name="stories[]" value="<?php the_ID(); ?>" <?php echo in_array(get_the_ID(), $_SESSION["stories"] ?? []) ? 'checked' : ''; ?>>
                            <?php echo get_the_date(); ?> | <?php the_title(); ?>
                        </label><br>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else: ?>
                    <p>No posts found within the selected date range.</p>
                <?php endif; ?>
            </div>

            <p><input type="submit" name="save_selection" value="Save Selection" class="button-primary"></p>
        </form>
    </div>
    <?php
}

// Retrieve category filters for a newsletter
function cnp_get_newsletter_categories($newsletter_id) {
    $categories = get_option("newsletter_{$newsletter_id}_categories", []);
    return is_array($categories) ? array_map('intval', $categories) : [];
}
