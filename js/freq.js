
const freq = ($ =>
{
    let host = '';

    function get(url, data = null, callback = null) {

        $.getJSON(url, data, res => reqCallback(res.data, callback));
    }

    function post(url, data = null, callback = null) {
        
        $.post(url, data, res => reqCallback(res.data, callback));
    }

    function reqCallback(data, callback) {

        if (!data) return;

        if (data.exit) {
            return redirect();
        }

        if (callback) {
            callback(data.data);
        }
    }

    function setHost(url) {
        host = url;
    }

    function redirect() {
        window.location.replace(host);
    }
    
    return {
        get: get,
        post: post,
        setHost: setHost,
        redirect: redirect
    }
})(jQuery);