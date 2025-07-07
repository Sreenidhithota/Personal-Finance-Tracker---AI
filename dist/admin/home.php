 <?php
// Chart data for expense categories (moved to top)
$labels = [];
$data = [];

$expenses = $conn->query("SELECT c.category, SUM(rb.amount) as total 
  FROM running_balance rb 
  INNER JOIN categories c ON rb.category_id = c.id 
  WHERE rb.balance_type = 2 
  GROUP BY rb.category_id");

while ($row = $expenses->fetch_assoc()) {
  $labels[] = $row['category'];
  $data[] = $row['total'];
}
?>
<style>
  .info-tooltip, .info-tooltip:focus, .info-tooltip:hover {
    background: unset;
    border: unset;
    padding: unset;
  }
</style>

<h1>Welcome to <?php echo $_settings->info('name') ?></h1>
<hr>

<!-- Combined Wrapper for Stats and Pie Chart -->
<div class="row align-items-start">
  <!-- Budget Stat Boxes -->
  <div class="col-md-9 d-flex flex-wrap">
    <div class="col-12 col-sm-6 col-md-4">
      <div class="info-box">
        <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-money-bill-alt"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Current Overall Budget</span>
          <span class="info-box-number text-right">
            <?php
              $cur_bul = $conn->query("SELECT sum(balance) as total FROM `categories` where status = 1")->fetch_assoc()['total'];
              echo number_format($cur_bul);
            ?>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4">
      <div class="info-box mb-3">
        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-calendar-day"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Today's Budget Entries</span>
          <span class="info-box-number text-right">
            <?php 
              $today_budget = $conn->query("SELECT sum(amount) as total FROM `running_balance` WHERE category_id IN (SELECT id FROM categories WHERE status = 1) AND date(date_created) = '".date("Y-m-d")."' AND balance_type = 1")->fetch_assoc()['total'];
              echo number_format($today_budget);
            ?>
          </span>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-4">
      <div class="info-box mb-3">
        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-calendar-day"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Today's Budget Expenses</span>
          <span class="info-box-number text-right">
            <?php 
              $today_expense = $conn->query("SELECT sum(amount) as total FROM `running_balance` WHERE category_id IN (SELECT id FROM categories WHERE status = 1) AND date(date_created) = '".date("Y-m-d")."' AND balance_type = 2")->fetch_assoc()['total'];
              echo number_format($today_expense);
            ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Pie Chart -->
  <div class="col-md-3">
    <div class="card p-2 text-center">
      <h6 class="mb-2">Expenses by Category</h6>
      <div style="width: 200px; height: 200px; margin: auto;">
        <canvas id="expenseChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- AI Chatbot Section -->
<div id="chat-icon" title="Open Chatbot">
  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M20 2H4a2 2 0 0 0-2 2v16l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
  </svg>
</div>
<div id="chat-window">
  <div id="chat-header">Chat with Penny <span id="chat-close">&times;</span></div>
  <iframe src="https://www.chatbase.co/chatbot-iframe/mQeIW3Rwz0X2zmT7ghB7E" title="Chatbot"></iframe>
</div>
<style>
  #chat-icon { position: fixed; bottom: 20px; right: 20px; background-color: #007bff;
    width: 60px; height: 60px; border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.3); cursor: pointer;
    z-index: 9999; display: flex; align-items: center; justify-content: center; }
  #chat-icon svg { width: 30px; height: 30px; fill: white; }
  #chat-window { position: fixed; bottom: 90px; right: 20px; width: 350px; height: 500px;
    border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); background: white; display: none;
    flex-direction: column; z-index: 10000; }
  #chat-window iframe { flex: 1; border: none; border-radius: 10px; }
  #chat-header { background-color: #007bff; color: white; padding: 10px;
    border-top-left-radius: 10px; border-top-right-radius: 10px;
    font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
  #chat-close { cursor: pointer; font-weight: bold; font-size: 18px; }
</style>
<script>
  const chatIcon = document.getElementById('chat-icon');
  const chatWindow = document.getElementById('chat-window');
  const chatClose = document.getElementById('chat-close');
  chatIcon.addEventListener('click', () => {
    chatWindow.style.display = 'flex'; chatIcon.style.display = 'none';
  });
  chatClose.addEventListener('click', () => {
    chatWindow.style.display = 'none'; chatIcon.style.display = 'flex';
  });
</script>

<div class="row">
  <div class="col-lg-12">
    <h4>Current Budget in each Categories</h4>
    <hr>
  </div>
</div>

<div class="col-md-12 d-flex justify-content-center">
  <div class="input-group mb-3 col-md-5">
    <input type="text" class="form-control" id="search" placeholder="Search Category">
    <div class="input-group-append">
      <span class="input-group-text"><i class="fa fa-search"></i></span>
    </div>
  </div>
</div>

<div class="row row-cols-4 row-cols-sm-1 row-cols-md-4 row-cols-lg-4">
  <?php 
  $categories = $conn->query("SELECT * FROM `categories` WHERE status = 1 ORDER BY `category` ASC");
  while ($row = $categories->fetch_assoc()):
  ?>
  <div class="col p-2 cat-items">
    <div class="callout callout-info">
      <span class="float-right ml-1">
        <button type="button" class="btn btn-secondary info-tooltip" data-toggle="tooltip" data-html="true" title='<?php echo html_entity_decode($row['description']) ?>'>
          <span class="fa fa-info-circle text-info"></span>
        </button>
      </span>
      <h5 class="mr-4"><b><?php echo $row['category'] ?></b></h5>
      <div class="d-flex justify-content-end">
        <b><?php echo number_format($row['balance']) ?></b>
      </div>
    </div>
  </div>
  <?php endwhile; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
   const ctx = document.getElementById('expenseChart').getContext('2d');
const expenseChart = new Chart(ctx, {
  type: 'pie',
  data: {
    labels: <?php echo json_encode($labels); ?>,
    datasets: [{
      label: 'Expenses by Category',
      data: <?php echo json_encode($data); ?>,
      backgroundColor: [
        '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0',
        '#9966ff', '#ff9f40', '#c9cbcf'
      ]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          boxWidth: 12,
          padding: 8
        }
      }
    }
  }
});


  function check_cats(){
    if($('.cat-items:visible').length > 0){
      $('#noData').hide('slow')
    }else{
      $('#noData').show('slow')
    }
  }

  $(function(){
    $('[data-toggle="tooltip"]').tooltip({ html:true });
    check_cats();
    $('#search').on('input', function(){
      var _f = $(this).val().toLowerCase();
      $('.cat-items').each(function(){
        var _c = $(this).text().toLowerCase();
        $(this).toggle(_c.includes(_f));
      });
      check_cats();
    });
  });
</script>
