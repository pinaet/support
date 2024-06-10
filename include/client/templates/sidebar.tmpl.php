<?php
$BUTTONS = isset($BUTTONS) ? $BUTTONS : true;
?>
    <div class="sidebar pull-right">
<?php if ($BUTTONS) { ?>
        <div class="front-page-button flush-right">
<p>
<?php
    if ($cfg->getClientRegistrationMode() != 'disabled' || !$cfg->isClientLoginRequired()) { ?>
            <a href="open.php?topicId=12" style="display:block" class="blue button"><?php echo __('ICT Setup Request');?></a>
</p>
<p>
    <a href="open.php?topicId=13" style="display:block" class="blue button"><?php echo __('Staff ID Card Request');?></a>
</p>
<?php } ?>
<p>
            <a href="view.php" style="display:block" class="green button"><?php
                echo __('Check Ticket Status');?></a>
</p>
        </div>
<?php } ?>
        <div class="content"><?php
    if ($cfg->isKnowledgebaseEnabled()
        && ($faqs = FAQ::getFeatured()->select_related('category')->limit(5))
        && $faqs->all()) { ?>
            <section><center><div class="header"><a href="https://sites.google.com/harrowschool.ac.th/ictservice"><?php echo __('User Guides'); ?></a></div></center>
<?php   foreach ($faqs as $F) { ?>
            <!-- <div><a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php
                echo urlencode($F->getId());
                ?>"><?php echo $F->getLocalQuestion(); ?></a>
                </div> -->
<?php   } ?>
            </section>
<?php
    }
    $resources = Page::getActivePages()->filter(array('type'=>'other'));
    if ($resources->all()) { ?>
            <section><div class="header"><?php echo __('Other Resources'); ?></div>
<?php   foreach ($resources as $page) { ?>
            <div><a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug();
            ?>"><?php echo $page->getLocalName(); ?></a></div>
<?php   } ?>
            </section>
<?php
    }
        ?></div>
    </div>

