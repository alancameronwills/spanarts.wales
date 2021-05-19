<?php 
/*
 * Template Name: Project
 * Template Post Type: post
 */

extract(bridge_qode_get_blog_single_params()); ?>
<?php get_header(); ?>
<?php if (have_posts()) : ?>
<?php while (have_posts()) : the_post(); ?>

					<div class="full_width topgap"  <?php if($background_color != "") { echo " style='background-color:". $background_color ."'";} ?>>
						<?php if(isset($bridge_qode_options['overlapping_content']) && $bridge_qode_options['overlapping_content'] == 'yes') {?>
							<div class="overlapping_content"><div class="overlapping_content_inner">
						<?php } ?>
						<div class="full_width_inner" <?php bridge_qode_inline_style($content_style_spacing); ?>>

					<?php if(($sidebar == "default")||($sidebar == "")) : ?>
						<div <?php bridge_qode_class_attribute(implode(' ', $single_class)) ?>>
                        <?php 
							get_template_part('templates/span-blog_single', 'loop');
						?>
                        
                        </div>

                    <?php elseif($sidebar == "1" ): ?>
							<div class="two_columns_66_33 background_color_sidebar grid2 clearfix">
							<div class="column1">					
									<div class="column_inner">
										<div <?php bridge_qode_class_attribute(implode(' ', $single_class)) ?>>
											<?php
											get_template_part('templates/' . $single_loop, 'loop');
											?>
										</div>
										
										<?php
											if($blog_hide_comments != "yes"){
												comments_template('', true); 
											}else{
												echo "<br/><br/>";
											}
										?> 
									</div>
								</div>	
								<div class="column2"> 
									<?php get_sidebar(); ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
                <?php if(isset($bridge_qode_options['overlapping_content']) && $bridge_qode_options['overlapping_content'] == 'yes') {?>
                    </div></div>
                <?php } ?>
                 </div>
<?php endwhile; ?>
<?php endif; ?>	


<?php get_footer(); ?>	