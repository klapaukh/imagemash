var activePairIndex = 0;
var pairs = [];
window.addEventListener('load', function() {
    var pairsEl = document.getElementById('pairs');
    pairs = pairsEl.getElementsByClassName('pair');
    for (var i=0; i<pairs.length; i++) {
        var pair = pairs[i];
        var images = pair.getElementsByClassName('image');
        for (var j=0; j<images.length; j++) {
            images[j].addEventListener('click', imageClicked);
        }
    }
    var counter = document.getElementById('counter');
    counter.style.left = (pairs[0].offsetLeft - 100 + counter.offsetLeft) + 'px';
    counter.style.visibility = 'visible';
});

function imageClicked() {
    var me = this.dataset.me;
    var other = this.dataset.other;
    logBeat(me, other);
    pairs[activePairIndex++].classList.remove('active');
    pairs[activePairIndex].classList.add('active');
    if (activePairIndex < pairs.length - 1) {
        var cu = document.getElementById('counter-upto');
        cu.removeChild(cu.lastChild);
        cu.appendChild(document.createTextNode(activePairIndex + 1))
    }
}

function logBeat(winner, loser) {
    var log = document.getElementById('log');
    if (log) {
        var li = document.createElement('li');
        li.appendChild(document.createTextNode(winner + ' beat ' + loser));
        log.appendChild(li);
    }
    console.log(winner + ' beat ' + loser);
    var req = new XMLHttpRequest();
    req.open('POST', '?winner=' + winner + '&loser=' + loser, true);
    req.send(token);
}
