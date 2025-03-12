<div class="form-group">
    <label for="csrInput" class="d-block">CSR</label>
    <textarea name="csr" id="csrInput" class="d-block form-control">
-----BEGIN CERTIFICATE REQUEST-----
MIICzTCCAbUCAQAwgYcxFDASBgNVBAMMC2F3ZGF3ZHcuY29tMQswCQYDVQQGEwJU
RzERMA8GA1UECAwIQXJrYW5zYXMxCjAIBgNVBAcMAWExIDAeBgkqhkiG9w0BCQEW
EXRlc3QyQGV4YW1wbGUuY29tMSEwHwYDVQQKDBhJbnRlcm5ldCBXaWRnaXRzIFB0
eSBMdGQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDN5bCpfA/15Fx0
J5+dJETk+5jZ0N3zGE8ubmw/ME1i+/3/yEWTSUuIXy7vk61kfOJaWajqu+Zy69db
YMDrQ8UKXVNezMn+teXvPgH2ZSH92H1HLwqjjy6h0LyFLYEikqFi8oY1lgFXrMv1
n0DIdDnDBNsKgoRSApuFz6gvzvtltx42JUWis1hY4kKfFz+pwrbKpmL5xbDanU7E
Y9g2buR8cu2Lwu+p9cHPJZ+ro2VsUpL01hNzToXEdTv3puYPv4pn85n0FpI1HOoB
uO0ViAp45LFI1A0UzLpuZ+lUAIQTYyQOfiKd1NPDM6j6DwETRV+vD50xQakw8gBl
VUaGJCR7AgMBAAGgADANBgkqhkiG9w0BAQUFAAOCAQEAW6jvcWGUX9wfiSEp0w+f
KfgCLWGwY/q8r5Hi5AO93FI9Gb0O00Ke3ypJPovSY7WFwYbkObjVHygSdO+4mIbJ
k8b4fTEnyMcRWDjy22SP0TNoGTuer8ecFcIEj/RbnNerRiYctTWxFBWpBRBH7Kh7
gL+IOLgk7OktrpE+nuY6z1es/w1CTwjZiiRNZ4zrRlXF/G1mUhLeQ0Aw9/FkGqPn
C1AXxv/XJ1sIuIaKmAjrVj57vSy837dNVXHEnYR1ZMwZZ6qDYU5EBWLTIC+VKAs1
UgE9H8dKG9s6iVgt3ozfDXMMohUhOSDTxfvEBmVPp+YhC8XXC0PWiiWj9qZFgePS
vw==
-----END CERTIFICATE REQUEST-----
                            </textarea>
    <button type="button" id="generateCsrBtn" class="btn btn-default d-block">Generate CSR</button>
    <label for="dcv">DCV</label>
    <select name="dcv" id="dcv" class="form-control">
        <option value="EMAIL">E-Mail</option>
        <option value="HTTP">HTTP</option>
        <option value="DNS">DNS</option>
    </select>
    <div id="approveremails">
        <label for="approveremail">Approver E-mail</label>
        <select id="approveremail" name="approveremail" class="form-control"></select>
    </div>
</div>
<script>
    $(function() {
        $('input[name="CN"]').on('change', e => {
            const token = $('input[name="token"]').val();
            const commonName = e.target.value;
            let serviceUrl = 'index.php?action=approverEmails&json=1' +
                '&commonName=' + commonName +
                '&token=' + token;
            $.ajax({
                url: serviceUrl,
                type: "GET",
                json: 1,
                success: function (ret) {
                    const data = JSON.parse(ret);
                    if (data.success) {
                        {literal}
                        data.emails[commonName].forEach(email =>
                            $('select[name="approveremail"]')
                                .append(`<option value ='${email}'>${email}</option>`)
                        )
                        {/literal}
                    }
                },
                error: function (e) {
                    console.error(e);
                },
            });
        });

        $('select[name="dcv"]').on('change', e => {
            if (e.target.value === 'EMAIL') {
                $('#approveremails').show();
            } else {
                $('#approveremails').hide();
            }
        })
    });
</script>