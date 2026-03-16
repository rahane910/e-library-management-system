function toggleMenu(icon) {
  icon.classList.toggle("active");
  document.getElementById("menu").classList.toggle("show");
}


// pdf section only for sample test

// PDF.js Viewer
const url = "tcs (1).pdf"; // 👉 put your PDF file name here
let pdfDoc = null,
    pageNum = 1,
    pageIsRendering = false,
    pageNumPending = null;
const scale = 1.2;
const canvas = document.getElementById("pdf-render");
const ctx = canvas.getContext("2d");

// Render page
const renderPage = num => {
  pageIsRendering = true;

  pdfDoc.getPage(num).then(page => {
    const viewport = page.getViewport({ scale });
    canvas.height = viewport.height;
    canvas.width = viewport.width;

    const renderCtx = {
      canvasContext: ctx,
      viewport
    };

    page.render(renderCtx).promise.then(() => {
      pageIsRendering = false;
      if (pageNumPending !== null) {
        renderPage(pageNumPending);
        pageNumPending = null;
      }
    });

    document.getElementById("page-num").textContent = num;
  });
};

// Check for pages rendering
const queueRenderPage = num => {
  if (pageIsRendering) {
    pageNumPending = num;
  } else {
    renderPage(num);
  }
};

// Show Prev Page
function prevPage() {
  if (pageNum <= 1) return;
  pageNum--;
  queueRenderPage(pageNum);
}

// Show Next Page
function nextPage() {
  if (pageNum >= pdfDoc.numPages) return;
  pageNum++;
  queueRenderPage(pageNum);
}

// Get Document
pdfjsLib.getDocument(url).promise.then(pdfDoc_ => {
  pdfDoc = pdfDoc_;
  document.getElementById("page-count").textContent = pdfDoc.numPages;
  renderPage(pageNum);
});
