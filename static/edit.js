function update()
{
            var txt = document.getElementById('txt').value;
            var xhtt = new XMLHttpRequest();
            xhtt.open("POST","preview.php",true);
            xhtt.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhtt.send('txt=' + encodeURIComponent(txt));
            xhtt.onreadystatechange = function () {
                document.getElementById('preview').innerHTML = xhtt.responseText;
            }
}
window.addEventListener('load', function () {
    update(); document.getElementById('updateBtn').addEventListener('click',update); });

