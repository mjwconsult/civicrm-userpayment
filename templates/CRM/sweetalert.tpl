{literal}
  <script type="text/javascript">
    var crmAlert = function ($type, $message) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({type: $type, title: $message});
      }
      else {
        alert($message);
      }
    };
    window.crmAlert = crmAlert;
  </script>
{/literal}
