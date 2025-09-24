<script>
  // Global loading indicator and error handling
  let isLoading = false;
  let errorCount = 0;
  const MAX_ERRORS = 3;

  // Show/hide loading indicator
  function showLoading(show) {
    isLoading = show;
    const existingIndicator = document.getElementById('globalLoadingIndicator');
    
    if (show && !existingIndicator) {
      const indicator = document.createElement('div');
      indicator.id = 'globalLoadingIndicator';
      indicator.innerHTML = `
        <div style="
          position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
          background: rgba(0,0,0,0.5); z-index: 9999; display: flex; 
          align-items: center; justify-content: center;
        ">
          <div style="
            background: white; padding: 20px; border-radius: 8px; 
            text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
          ">
            <div style="margin-bottom: 10px;">⏳ Loading...</div>
            <div style="width: 40px; height: 4px; background: #e0e0e0; border-radius: 2px; overflow: hidden;">
              <div style="width: 100%; height: 100%; background: #007bff; animation: loading 1s infinite;"></div>
            </div>
          </div>
        </div>
        <style>
          @keyframes loading { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        </style>
      `;
      document.body.appendChild(indicator);
    } else if (!show && existingIndicator) {
      existingIndicator.remove();
    }
  }

  // Global error handler
  function handleError(error, context = '') {
    console.error(`Error in ${context}:`, error);
    errorCount++;
    
    if (errorCount >= MAX_ERRORS) {
      showLoading(false);
      alert('Multiple errors occurred. Please refresh the page or check your connection.');
      errorCount = 0;
    }
  }

  // Safe fetch wrapper with error handling
  async function safeFetch(url, options = {}) {
    if (isLoading) return null;
    
    try {
      showLoading(true);
      const response = await fetch(url, options);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return response;
    } catch (error) {
      handleError(error, 'fetch');
      return null;
    } finally {
      setTimeout(() => showLoading(false), 500);
    }
  }

  // Sidebar functionality
  document.addEventListener('click', function (evt) {
    const isSmall = window.matchMedia('(max-width: 920px)').matches;
    if (!isSmall) return;
    const sidebar = document.querySelector('.sidebar');
    const menuBtn = document.querySelector('.menu-button');
    const clickedInsideSidebar = sidebar.contains(evt.target);
    const clickedMenuBtn = menuBtn.contains(evt.target);
    if (!clickedInsideSidebar && !clickedMenuBtn) {
      document.body.classList.remove('sidebar-open');
    }
  });

  // Modal functionality
  function openModal() {
    document.getElementById('studentModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  }

  function closeModal() {
    document.getElementById('studentModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
  }

  // Event listeners for modal
  document.addEventListener('DOMContentLoaded', function() {
    // Create New button click handler
    const createNewLink = document.getElementById('createNewLink');
    if (createNewLink) {
      createNewLink.addEventListener('click', function(e) {
        e.preventDefault();
        openModal();
      });
    }

    // Close modal when clicking outside
    const modal = document.getElementById('studentModal');
    if (modal) {
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          closeModal();
        }
      });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
      }
    });

    // Form button handlers
    const saveBtn = document.querySelector('.btn-save');
    const updateBtn = document.querySelector('.btn-update');
    
    if (saveBtn) {
      saveBtn.addEventListener('click', function() {
        // Add save functionality here
        console.log('Save button clicked');
        // You can add form validation and submission logic here
      });
    }
    
    if (updateBtn) {
      updateBtn.addEventListener('click', function() {
        // Add update functionality here
        console.log('Update button clicked');
        // You can add form update logic here
      });
    }
  });
</script> 