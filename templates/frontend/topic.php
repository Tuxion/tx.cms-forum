<?php namespace components\forum; if(!defined('TX')) die('No direct access.');


//Load plugins.
echo load_plugin('jquery_rest');
echo load_plugin('jquery_timeago');
echo load_plugin('highlight_js');

//Reference user, for easy access.
$user = &tx('Account')->user;

//Create an unique forum identifier.
$form_id = 'form'.tx('Security')->random_string(10);

$pagination = function()use($data){
  ?>
    <div class="pagination pagination-small">
      
      <ul>
        
        <li<?php if($data->pager->check('first_page')) echo ' class="disabled"'; ?>>
          <a href="<?php echo $data->pager->link_first; ?>">&laquo;</a>
        </li>
        
        <?php foreach($data->pager->pages as $page): ?>
        <li<?php if($page->check('active')) echo ' class="active"'; ?>>
          <a href="<?php echo $page->link; ?>"><?php echo $page->nr; ?></a>
        </li>
        <?php endforeach; ?>
        
        <li<?php if($data->pager->check('last_page')) echo ' class="disabled"'; ?>>
          <a href="<?php echo $data->pager->link_last; ?>">&raquo;</a>
        </li>
        
      </ul>
      
    </div>
  <?php
};

?>

<!-- Topic-starter. -->
<section id="topic-starter" class="topic-starter-topic">
  
  <div class="topic-starter-header clearfix">
    <h1 class="topic-starter-title"><?php echo $data->topic->title; ?></h1>
    <?php $pagination(); ?>
    
    <!-- #TODO: Make this button nice. -->
    <div class="topic-operations">
      
      <?php if(mk('Account')->check_level(2)): ?>
      
      <?php
        echo $data->target_fora
          ->as_options('new_forum_id', 'title', 'id', array(
            'id' => "topic_mover_$form_id",
            'tooltip' => 'description',
            'indent_field' => 'depth',
            'class' => 'forum-topic-mover',
            'placeholder_text' => __('forum', 'Move forum',true)
          ));
      ?>
      
      <a data-topic-id="<?php echo $data->topic->id; ?>" class="btn-delete-topic btn" href="#">
        <?php __('forum', 'Delete topic'); ?>
      </a>
      <?php endif; ?>
      <?php if($data->check('show_reply')): ?>
      <a data-actions="focus-reply-form scroll-reply-form" class="btn" href="#reply-form">
        <?php __('forum', 'Reply'); ?>
      </a>
      <?php endif; ?>
      
      <?php if(mk('Account')->check_level(1)) {
        
        $sub = $data->topic->extra->is_subscribed->is_true();
        
        ?>
          <a class="btn-unsubscribe" data-topic-id="<?php echo $data->topic->id; ?>" href="#"<?php echo $sub ? '' : ' style="display:none;"'; ?>><?php __('forum', 'Unsubscribe'); ?></a>
          <a class="btn-subscribe" data-topic-id="<?php echo $data->topic->id; ?>" href="#"<?php echo $sub ? ' style="display:none;"' : ''; ?>><?php __('forum', 'Subscribe'); ?></a>
        <?php
        
      } ?>
      
      
    </div>
    
  </div>
  
  <div<?php if(!$data->check('show_starter')) echo ' hidden'; ?>>
    <?php echo mk('Component')->sections('forum')->get_html('reply', $data->starter); ?>
  </div>
  
</section>

<!-- Replies. -->
<section id="replies" class="forum-topic-replies">
  
  <h1><?php __('forum', 'Replies'); ?></h1>
  
  <?php foreach($data->replies as $reply): ?>
  <?php echo tx('Component')->sections('forum')->get_html('reply', $reply); ?>
  <?php endforeach; ?>
  
</section>

<section id="footer-pagination">
  <?php $pagination(); ?>
</section>

<?php if($data->check('show_reply')): ?>

<!-- Reply form. -->
<section id="reply-form" class="forum-topic-reply-form">
  
  <form method="POST" action="<?php echo url('?rest=forum/post'); ?>" id="<?php echo $form_id; ?>">

    <fieldset class="">

      <legend><?php __('forum', 'Reply'); ?></legend>
      
      <input type="hidden" name="id" value="" />
      <input type="hidden" name="topic_id" value="<?php echo $data->topic->id; ?>" />
      
      <div class="">
        <div class="control-group">
          <textarea id="<?php echo $form_id; ?>-textarea-new-post" rows="10" class="input-block-level markdownarea" name="content" placeholder="Enter message here"></textarea>
          <div id="epiceditor"></div>
        </div>
        <div class="control-group button-set">
          <!-- <button class="btn btn btn-link" id="btn-preview">Preview</button> -->
          <input class="btn" name="submit" type="submit" value="<?php __('forum', 'Reply'); ?>" />
        </div>
      </div>
    </fieldset>

  </form>
  
</section>

<script>
  $(function(){
    
    //Create a new EpicEditor and pass the options.
    var editor = new EpicEditor({
      textarea: '<?php echo $form_id; ?>-textarea-new-post',
      localStorageName: 'forum_topic_reply_drafts_epiceditor',
      file:{
        name: 'topic_<?php echo $data->topic->id; ?>',
        autosave: 1000
      },
      theme: {
        editor: '/themes/editor/epic-light.css',
        preview: '../../../../bootstrap/css/bootstrap.min.css'
      },
      button: {
        fullscreen: false
      }
    });
    
    //Place it in the DOM.
    editor.load(function(){
      
      //Now that we are sure there were no javascript errors, hide the textarea.
      $('#<?php echo $form_id; ?>-textarea-new-post').hide();
      
      //Enable CTRL + Enter submitting
      $(editor.getElement('editor')).on('keydown', function(e){
        if(e.ctrlKey === true && e.keyCode === 13)
          $('#reply-form form').trigger('submit');
      });
      
    });
    
    window.onresize = function () {
      editor.reflow();
    }
    
    $('#reply-form form').restForm({
      success: function(data){
        editor.remove('topic_<?php echo $data->topic->id; ?>');
        
        var loadLastPostUrl = "<?php echo $data->pager->link_after_reply; ?>";
        
        if(document.location == loadLastPostUrl || document.location == loadLastPostUrl+'#latest')
          document.location.reload();
        else
          document.location = loadLastPostUrl+'#latest';
        
      }
    });
    
    $('.btn-subscribe').on('click', function(e){
      e.preventDefault();
      $.rest('PUT', '?rest=forum/topic_subscription/'+$(this).attr('data-topic-id'))
        .done(function(){
          $('.btn-subscribe').hide();
          $('.btn-unsubscribe').show();
        });
    });
    
    $('.btn-unsubscribe').on('click', function(e){
      e.preventDefault();
      $.rest('DELETE', '?rest=forum/topic_subscription/'+$(this).attr('data-topic-id'))
        .done(function(){
          $('.btn-unsubscribe').hide();
          $('.btn-subscribe').show();
        });
    });
    
  });

  <?php if(tx('Account')->check_level(2)): ?>
    
    //Delete topic.
    $(function(){

      $('.btn-delete-topic').on('click', function(e){
        
        e.preventDefault();
        
        if(confirm('Are you sure you want to delete this topic?')){
          $.rest('GET', "<?php echo url('?rest=forum/delete_topic/',1); ?>"+$(e.target).data('topic-id'))
            .done(function(result){
              document.location = "<?php echo url('pid=KEEP&fid=KEEP',1); ?>";
            });
        }
        
      });
      
      //Edit post.
      $('.btn-edit-post').on('click', function(e){
        
        e.preventDefault();
        
        //Show edit form.
        

        //Get post data and fill the form with it.
        $.rest('GET', "<?php echo url('?rest=forum/delete_topic/', 1); ?>"+$(e.target).data('post-id'))
          .done(function(result){
            
            

          });
        
      });
      
      //Delete post.
      $('.btn-delete-post').on('click', function(e){
        
        e.preventDefault();
        
        var that = $(this);
        
        if(confirm('Are you sure you want to delete this post?')){
          $.rest('GET', "<?php echo url('?rest=forum/delete_post/',1); ?>"+$(e.target).data('post-id'))
            .done(function(result){
              $(that).closest('.forum-topic-reply').slideUp();
            });
        }
        
      });
      
      //Move topic.
      $('#<?php echo "topic_mover_$form_id"; ?>').on('change', function(e){
        
        if(confirm('Are you sure you want to move this topic?')){
          $.rest('POST', "<?php echo url('?rest=forum/topic_move/'.$data->topic->id); ?>", {forum_id: $(e.target).val()})
            .done(function(result){
              window.location = result.link.output;
            });
        }
        
      });
      
    });
    
  <?php endif; ?>
  
</script>

<?php endif; ?>

<script type="text/javascript">
  
  function ucFirst(string) {
    return string.charAt(0).toUpperCase() + string.substring(1);
  }
  
  jQuery(function($){
    
    $('time').each(function(){
      
      var $el = $(this);
      
      var inWords = $.timeago($el.attr('datetime'));
      
      if($el.attr('data-ucfirst') == 'true')
        inWords = ucFirst(inWords);
      
      $el
        .text(inWords)
        .attr('title', $el.attr('datetime'));
      
    });
    
    // if(window.Rainbow && Rainbow.color){
    //   $('code').each(function(){
    //     $this = $(this);
    //     $this.attr('data-language', $this.attr('class'));
    //   });
    //   Raibow.color();
    // }
    
    if(window.hljs){
      hljs.initHighlightingOnLoad();
    }
    
  });
  
</script>