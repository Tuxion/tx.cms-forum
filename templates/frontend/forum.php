<?php echo load_plugin('bootstrap'); ?>

<?php echo $data->breadcrumbs; ?>
<section class="tx-forum row-fluid alpha">
  
<?php echo $data->section; ?>

</section>
<?php echo load_plugin('jquery_timeago'); ?>

<script>
  $(function(){
    $('time').text(function(){
      var dt = new Date($(this).attr('datetime'));
      return dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString();
    }).timeago();
  });
</script>