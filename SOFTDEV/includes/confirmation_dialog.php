<!-- Confirmation Dialog -->
<div id="confirmationModal" class="modal confirmation-modal" style="display: none;">
  <div class="modal-content confirmation-content">
    <div class="confirmation-header">
      <h3>Confirm Save Student</h3>
    </div>
    <div class="confirmation-body">
      <p>Are you sure you want to save this student?</p>
      <div class="student-summary" id="studentSummary">
        <!-- Student details will be populated here -->
      </div>
    </div>
    <div class="confirmation-actions">
      <button type="button" class="btn btn-confirm" id="confirmSaveBtn">Confirm Save</button>
      <button type="button" class="btn btn-back" id="backToFormBtn">Back to Form</button>
    </div>
  </div>
</div>

<!-- Success Message Modal -->
<div id="successModal" class="modal success-modal" style="display: none;">
  <div class="modal-content success-content">
    <div class="success-header">
      <h3><i class="fas fa-check-circle"></i> Email Verification Sent</h3>
    </div>
    <div class="success-body">
      <p id="successMessage"></p>
      <div class="success-info">
        <p><strong>Next Steps:</strong></p>
        <ul>
          <li>Check the student's email inbox</li>
          <li>Look for the verification email from Student Management System</li>
          <li>Click the "Verify Email Address" button in the email</li>
          <li>Student registration will be completed after verification</li>
        </ul>
      </div>
    </div>
    <div class="success-actions">
      <button type="button" class="btn btn-success" id="closeSuccessBtn">Got it!</button>
    </div>
  </div>
</div>

<style>
.confirmation-modal {
  position: fixed;
  z-index: 1001;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.confirmation-content {
  background-color: #fff;
  border-radius: 8px;
  padding: 0;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.confirmation-header {
  background-color: #f8f9fa;
  color: #333;
  padding: 16px 20px;
  border-radius: 8px 8px 0 0;
  border-bottom: 1px solid #dee2e6;
}

.confirmation-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.confirmation-body {
  padding: 20px;
  text-align: center;
}

.confirmation-body p {
  margin: 0 0 16px 0;
  font-size: 1rem;
  color: #333;
}

.student-summary {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 12px;
  margin: 12px 0;
  text-align: left;
}

.student-summary h4 {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 0.9rem;
  font-weight: 600;
}

.student-summary .summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 6px;
  padding: 2px 0;
  border-bottom: 1px solid #e9ecef;
}

.student-summary .summary-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.student-summary .summary-label {
  font-weight: 500;
  color: #666;
  min-width: 100px;
}

.student-summary .summary-value {
  color: #333;
  text-align: right;
  flex: 1;
}

.confirmation-actions {
  padding: 16px 20px;
  display: flex;
  gap: 10px;
  justify-content: center;
  border-top: 1px solid #dee2e6;
}

.btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
  min-width: 100px;
}

.btn-confirm {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}

.btn-confirm:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.btn-back {
  background-color: #6c757d;
  color: white;
  border-color: #6c757d;
}

.btn-back:hover {
  background-color: #545b62;
  border-color: #545b62;
}

@media (max-width: 768px) {
  .confirmation-content {
    width: 95%;
    margin: 20px;
  }
  
  .confirmation-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}

/* Success Modal Styles */
.success-modal {
  position: fixed;
  z-index: 1002;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-content {
  background-color: #fff;
  border-radius: 8px;
  padding: 0;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.success-header {
  background-color: #d4edda;
  color: #155724;
  padding: 16px 20px;
  border-radius: 8px 8px 0 0;
  border-bottom: 1px solid #c3e6cb;
}

.success-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.success-header i {
  color: #28a745;
  font-size: 1.2em;
}

.success-body {
  padding: 20px;
  text-align: left;
}

.success-body p {
  margin: 0 0 16px 0;
  font-size: 1rem;
  color: #333;
  text-align: center;
  font-weight: 500;
}

.success-info {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 12px;
  margin: 12px 0;
}

.success-info p {
  margin: 0 0 8px 0;
  color: #333;
  font-size: 0.9rem;
  font-weight: 600;
  text-align: left;
}

.success-info ul {
  margin: 0;
  padding-left: 20px;
  color: #666;
  font-size: 0.9rem;
}

.success-info li {
  margin-bottom: 4px;
}

.success-actions {
  padding: 16px 20px;
  display: flex;
  gap: 10px;
  justify-content: center;
  border-top: 1px solid #dee2e6;
}

.btn-success {
  background-color: #28a745;
  color: white;
  border-color: #28a745;
}

.btn-success:hover {
  background-color: #218838;
  border-color: #1e7e34;
}

@media (max-width: 768px) {
  .success-content {
    width: 95%;
    margin: 20px;
  }
  
  .success-actions {
    flex-direction: column;
  }
  
  .btn-success {
    width: 100%;
  }
}
</style>
