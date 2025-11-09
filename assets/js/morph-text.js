/* morph-text.js
   Morphing text effect (based on the user-provided snippet).
   Expects the page to contain:
     <div id="container"><span id="text1"></span><span id="text2"></span></div>
   and an SVG filter with id="threshold" (we add the SVG inline in index.html).
*/
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const elts = {
    text1: document.getElementById('text1'),
    text2: document.getElementById('text2')
  };

  if (!elts.text1 || !elts.text2) return; // nothing to do

  // The strings to morph between. Changed to school welcome phrase.
  const texts = [
    'Welcome',
    'to',
    'Katikamu',
    'SDA',
    'SS'
  ];

  // Controls the speed of morphing.
  const morphTime = 1; // seconds
  const cooldownTime = 0.25; // seconds

  let textIndex = texts.length - 1;
  let time = new Date();
  let morph = 0;
  let cooldown = cooldownTime;

  elts.text1.textContent = texts[textIndex % texts.length];
  elts.text2.textContent = texts[(textIndex + 1) % texts.length];

  function doMorph() {
    morph += (new Date() - time) / 1000;
    cooldown = 0;

    let fraction = morph / morphTime;

    if (fraction > 1) {
      cooldown = cooldownTime;
      fraction = 1;
    }

    setMorph(fraction);
  }

  // A lot of the magic happens here, this is what applies the blur filter to the text.
  function setMorph(fraction) {
    // guard
    fraction = Math.max(0, Math.min(1, fraction));

    // avoid division by zero
    const f1 = Math.max(fraction, 0.0001);
    const f2 = Math.max(1 - fraction, 0.0001);

    elts.text2.style.filter = `blur(${Math.min(8 / f1 - 8, 100)}px)`;
    elts.text2.style.opacity = `${Math.pow(fraction, 0.4) * 1}`;

    elts.text1.style.filter = `blur(${Math.min(8 / f2 - 8, 100)}px)`;
    elts.text1.style.opacity = `${Math.pow(1 - fraction, 0.4) * 1}`;

    elts.text1.textContent = texts[textIndex % texts.length];
    elts.text2.textContent = texts[(textIndex + 1) % texts.length];
  }

  function doCooldown() {
    morph = 0;

    elts.text2.style.filter = '';
    elts.text2.style.opacity = '1';

    elts.text1.style.filter = '';
    elts.text1.style.opacity = '0';
  }

  // Animation loop, which is called every frame.
  function animate() {
    requestAnimationFrame(animate);

    let newTime = new Date();
    let dt = (newTime - time) / 1000;
    time = newTime;

    if (cooldown > 0) {
      cooldown -= dt;
      if (cooldown <= 0) {
        textIndex++;
      }
      doCooldown();
    } else {
      morph += dt;
      if (morph > morphTime) {
        morph = morphTime; // cap until cooldown triggers
      }
      doMorph();
    }
  }

  // Start the loop
  // Set initial styles so text1 is visible only after morph begins
  elts.text1.style.opacity = '1';
  elts.text2.style.opacity = '0';
  morph = 0;
  cooldown = cooldownTime;
  time = new Date();
  requestAnimationFrame(animate);
});
