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
    counter.style.left = (pairs[0].offsetLeft) + 'px';
    var sourcelink = document.getElementById('get-source');
    sourcelink.addEventListener('click', function(e) {
        e.preventDefault();
        var obj = document.createElement('object');
        obj.style.border = '2px solid black';
        obj.style.position = 'absolute';
        obj.style.width = this.offsetWidth + 'px';
        obj.style.height = '0px';
        obj.style.display = 'block';
        obj.style.zIndex = '100';
        obj.style.top = this.offsetTop + this.offsetHeight + 'px';
        obj.style.left = this.offsetLeft + 'px';
        obj.addEventListener('load', function() {
            obj.style.width = '300px';
            obj.style.height = (obj.contentDocument.body.offsetHeight + 30) + 'px';
        });
        obj.data = this.href + '&embed=1';
        document.body.appendChild(obj);
    });
});

function start(){
    var mainheading = document.getElementById('mainheading');    
    var firstheading = document.getElementById('firstheading');    
    firstheading.style.display = 'none';
    mainheading.style.display = 'block';
    var counter = document.getElementById('counter');
    counter.style.visibility = 'visible';
    pairs[activePairIndex++].classList.remove('active');
    pairs[activePairIndex].classList.add('active');
    var cu = document.getElementById('counter-upto');
    cu.removeChild(cu.lastChild);
    cu.appendChild(document.createTextNode(activePairIndex))
}

function imageClicked() {
    var me = this.dataset.me;
    var other = this.dataset.other;
    var count = document.getElementById('counter-upto').innerHTML;
    logBeat(me, other,count);
    pairs[activePairIndex++].classList.remove('active');
    pairs[activePairIndex].classList.add('active');
    if (activePairIndex < pairs.length - 1) {
        var cu = document.getElementById('counter-upto');
        cu.removeChild(cu.lastChild);
        cu.appendChild(document.createTextNode(activePairIndex))
    }
}

function logBeat(winner, loser,index) {
    var log = document.getElementById('log');
    if (log) {
        var li = document.createElement('li');
        li.appendChild(document.createTextNode(index + ": " + winner + ' beat ' + loser));
        log.appendChild(li);
    }
    console.log(index + ": " + winner + ' beat ' + loser);
    var req = new XMLHttpRequest();
    req.open('POST', '?winner=' + winner + '&loser=' + loser + '&index=' + index, true);
    req.send(token);
}
