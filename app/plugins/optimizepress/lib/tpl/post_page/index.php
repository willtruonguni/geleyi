<?php
global $post;
$pb = (get_post_meta($post->ID,'_'.OP_SN.'_pagebuilder',true) == 'Y');
?><div id="op-post-page">
	<ul id="op-post-tabs" class="cf">
    <?php if ($pb) : ?>
        <li class="tab-wordpress"><a href="#wordpress">WordPress</a></li>
        <li class="tab-pagebuilder"><a href="#pagebuilder"><?php _e('OptimizePress',OP_SN); ?></a></li>
    <?php else : ?>
        <li class="tab-wordpress"><a href="#wordpress">WordPress</a></li>
        <li class="tab-settings"><a href="#settings"><?php _e('OptimizePress Settings',OP_SN); ?></a></li>
    <?php endif; ?>
    </ul>
    <?php if($pb): ?>
	<div id="op-pagebuilder-container" class="meta-box-sortables" style="display:none">
        <div class="postbox" id="op-post-settings-tab">
            <h3 class="hndle"><span><?php echo __('OptimizePress',OP_SN) ?></span></h3>
            <div class="inside">
            	<ul class="cf">
                	<li>
                		<img src="<?php echo OP_IMG; ?>page_builder-alt.png" height="100" width="100"  />
                		<div class="page-builder-indent">
                			<h4>PageBuilder</h4>
                			<p>Customize template settings and overall page design features.</p>
                			<a href="<?php echo menu_page_url(OP_SN.'-page-builder',false).'&amp;page_id='.$post->ID ?>" class="op-pagebuilder"><?php _e('Launch Now',OP_SN) ?></a>
                		</div>
                	</li>
                    <li>
                    	<img src="<?php echo OP_IMG; ?>live_editor-alt.png" height="100" width="100"  />
                    	<div class="page-builder-indent">
                    		<h4>LiveEditor</h4>
                    		<p>Add and edit content, modify the layout and insert page elements.</p>
                    		<a href="<?php echo menu_page_url(OP_SN.'-page-builder',false).'&amp;page_id='.$post->ID ?>&amp;step=5" class="op-pagebuilder"><?php _e('Launch Now',OP_SN) ?></a>
                    	</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php else: ?>
	<div id="op-settings-container" class="meta-box-sortables" style="display:none">
        <div class="postbox" id="op-post-settings-tab">
            <h3 class="hndle"><span><?php echo __('OptimizePress Settings',OP_SN) ?></span></h3>
            <div class="inside">
                <?php do_action(OP_SN.'-post_page-metas',$post)  ?>
            </div>
        </div>
    </div>
    <?php endif ?>
</div>