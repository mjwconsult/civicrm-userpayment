{literal}
  <script type="text/javascript">
    var crmAlert = function ($type, $message, $title) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({icon: $type, text: $message, title: $title});
      }
      else {
        alert($message);
      }
    };
    window.crmAlert = crmAlert;
  </script>
{/literal}
