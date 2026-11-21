// Proof-of-work: szukamy nonce, dla którego SHA-256(challenge + ":" + nonce) ma >= difficulty zerowych bitów.
function zeroBits(bytes) {
  let bits = 0;
  for (const b of bytes) {
    if (b === 0) { bits += 8; continue; }
    bits += Math.clz32(b) - 24;
    break;
  }
  return bits;
}

self.onmessage = async (e) => {
  const { challenge, difficulty } = e.data;
  const enc = new TextEncoder();
  const prefix = challenge + ":";
  const BATCH = 2000;
  let nonce = 0;
  for (;;) {
    const jobs = [];
    for (let i = 0; i < BATCH; i++) {
      jobs.push(crypto.subtle.digest("SHA-256", enc.encode(prefix + (nonce + i))));
    }
    const results = await Promise.all(jobs);
    for (let i = 0; i < BATCH; i++) {
      if (zeroBits(new Uint8Array(results[i])) >= difficulty) {
        self.postMessage({ nonce: nonce + i });
        return;
      }
    }
    nonce += BATCH;
    self.postMessage({ progress: nonce });
  }
};
