// ---------- helpers ----------
function triggerTxtDownload(fileName, text) {
  const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = fileName || `license-${Date.now()}.txt`;
  document.body.appendChild(a); a.click(); a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 0);
}
function buildLicenseText(transactionId, keys) {
  const lines = [
    'Congratulations! 🎉 Your payment was successful.',
    `Transaction: ${transactionId}`, '',
    'Your license information:'
  ];
  if (Array.isArray(keys) && keys.length) {
    if (typeof keys[0] === 'string') {
      keys.forEach((k,i)=>lines.push(`- Item ${i+1}: ${k}`));
    } else {
      keys.forEach((k,i)=>{
        const name = k.product || `Item ${i+1}`;
        const val = k.license_key || '(Pending — support will provide shortly)';
        lines.push(`- ${name}: ${val}`);
      });
    }
  } else {
    lines.push('- (No license was allocated. Contact support.)');
  }
  lines.push('', 'Keep this file safe. Thank you for your purchase!');
  return lines.join('\n');
}
function openModal(id){ const el=document.getElementById(id); if(el) el.style.display='flex'; }
function closeModal(id){ const el=document.getElementById(id); if(el) el.style.display='none'; }

// ---------- UI flows ----------
function showQR(qrDataUri) {
  const img = document.getElementById('qrImage');
  const status = document.getElementById('qrStatus');
  if (img) img.src = qrDataUri;
  if (status) status.textContent = 'Scan with Bakong to complete payment';
  openModal('qrModal');
}
function showSuccessAndKeys(transactionId, keys, downloadFileName) {
  // Build display text
  let viewText = '';
  if (Array.isArray(keys)) {
    if (keys.length && typeof keys[0] === 'string') viewText = keys.join('\n');
    else viewText = keys.map((k,i)=>`${k.product || `Item ${i+1}`}: ${k.license_key || '(Pending — support will provide shortly)'}`).join('\n');
  } else viewText = '(no license key allocated)';

  // Close QR modal and open success modal
  closeModal('qrModal');
  const txSuccess = document.getElementById('successTxId');
  if (txSuccess) txSuccess.value = transactionId;
  const textareaSuccess = document.getElementById('successLicenseText');
  if (textareaSuccess) textareaSuccess.value = viewText;
  openModal('successModal');

  // Auto download license file
  const txt = buildLicenseText(transactionId, keys || []);
  triggerTxtDownload(downloadFileName || `license-${transactionId}.txt`, txt);

  // Bind only the download button (no copy)
  const dlBtnSuccess = document.getElementById('downloadKeyBtnSuccess');
  if (dlBtnSuccess) dlBtnSuccess.onclick = () => triggerTxtDownload(downloadFileName || `license-${transactionId}.txt`, txt);
}

// ---------- polling ----------
function pollForPayment(md5, transactionId) {
  const status = document.getElementById('qrStatus');
  const timer = setInterval(async () => {
    try {
      const r = await fetch('/khqr/check', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ md5, transactionId })
      });
      const d = await r.json();
        if (d.status === 'success') {
        clearInterval(timer)
        const keys = Array.isArray(d.keys) ? d.keys : (Array.isArray(d.license_keys) ? d.license_keys : [])
        const fileName = d.downloadFileName || `license-${transactionId}.txt`
        showSuccessAndKeys(transactionId, keys, fileName) // shows modal section + copy/download
        }

    } catch {}
  }, 4000);

  setTimeout(()=>{ clearInterval(timer); if(status) status.textContent='Payment window expired. Please try again.'; }, 5*60*1000);
}

// ---------- start checkout ----------
async function createOrderAndShowQR(payload) {
  const res = await fetch('/checkout', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.qrCodeData && data.md5 && data.transaction_id) {
    const txInput = document.getElementById('currentTxId'); if (txInput) txInput.value = data.transaction_id;
    showQR(data.qrCodeData); pollForPayment(data.md5, data.transaction_id);
  } else {
    // optional toast/UI instead of alert
    console.error(data.error || 'Error creating order');
  }
}

// ---------- re-fetch keys (optional button) ----------
async function refreshKeysFromDB() {
  const tx = document.getElementById('currentTxId')?.value || '';
  if (!tx) return;
  try {
    const r = await fetch('/order/keys', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ transactionId: tx })
    });
    const d = await r.json();
    if (d.status === 'success') showSuccessAndKeys(d.transactionId, d.keys, d.downloadFileName);
  } catch {}
}

// ---------- “close” flow with custom confirm modal ----------
function requestCloseQrModalOrWarn() {
  // No license section inside QR modal anymore; just close it.
  closeModal('qrModal');
  const st=document.getElementById('qrStatus'); if(st) st.textContent='Generating QR...';
  const img=document.getElementById('qrImage'); if(img) img.src='';
}
function actuallyCloseQrModal() {
  closeModal('closeConfirmModal');
  closeModal('qrModal');
  const st=document.getElementById('qrStatus'); if(st) st.textContent='Generating QR...';
  const img=document.getElementById('qrImage'); if(img) img.src='';
}

// ---------- events ----------
document.addEventListener('click', async (e) => {
  if (e.target?.id === 'buyBtn') {
    const id = e.target.dataset.id;
    const name = (document.getElementById('buyerName')?.value || '').trim();
    const phone = (document.getElementById('buyerPhone')?.value || '').trim();
    const payload = { product_id: id };
    if (name) payload.buyer_name = name;
    if (phone) payload.buyer_phone = phone;
    await createOrderAndShowQR(payload);
  }

  if (e.target?.matches('[data-close]')) requestCloseQrModalOrWarn();
  if (e.target?.id === 'confirmCloseBtn') actuallyCloseQrModal();
  if (e.target?.id === 'cancelCloseBtn') closeModal('closeConfirmModal');
  if (e.target?.id === 'successCloseBtn') closeModal('successModal');

  if (e.target?.id === 'downloadQR') {
    const img=document.getElementById('qrImage'); if(!img || !img.src) return;
    const a=document.createElement('a'); a.href=img.src; a.download=`khqr-${Date.now()}.png`;
    document.body.appendChild(a); a.click(); a.remove();
  }

  // Success modal download if needed
  if (e.target?.id === 'downloadKeyBtnSuccess') {
    const tx=document.getElementById('successTxId')?.value || Date.now();
    const t=document.getElementById('successLicenseText'); if(!t) return;
    const txt = buildLicenseText(tx, t.value.split('\n').filter(Boolean).map((line,i)=>({
      product:`Item ${i+1}`, license_key: line.includes(': ')? line.split(': ').pop() : line
    })));
    triggerTxtDownload(`license-${tx}.txt`, txt);
  }
  document.getElementById('copyTxBtn').onclick = () => {
  const tx = document.getElementById('successTxId').value;
  navigator.clipboard.writeText(tx);
  alert('Transaction ID copied!');
};

document.getElementById('copyKeyBtn').onclick = () => {
  const keys = document.getElementById('successLicenseText').value;
  navigator.clipboard.writeText(keys);
  alert('License key(s) copied!');
};

});
