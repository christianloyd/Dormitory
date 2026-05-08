 <!-- Row 1 -->
        <div class="row g-3">
            <div class="col-md-3">
                <div class="stat-card" style="background-color:#FFADAD !important; color:#fff;">
                         <div class="stat-body">
                        <i class="fas fa-users"></i>
                        <div class="stat-text">
                            <h3><?php echo $tenantsCount; ?></h3>
                            <h5>Total Tenants</h5>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="../../modules/tenants/" class="view-details">View details</a></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background-color:#028121 !important; color:#fff;">
        
                    <div class="stat-body">
                        <i class="fas fa-door-open"></i>
                        <div class="stat-text">
                            <h3><?php echo $roomsCount; ?></h3>
                            <h5>Total Rooms</h5>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="../../modules/rooms/" class="view-details">View details</a></div>
                </div>
            </div>
            <div class="col-md-3">
                   <div class="stat-card" style="background-color:#BDB2FF !important; color:#fff;">
                    <div class="stat-body">
                        <i class="fas fa-bed"></i>
                        <div class="stat-text">
                            <h3><?php echo $roomsOccupied; ?></h3>
                            <h5>Rooms Occupied</h5>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="../../modules/rooms/occupied.php" class="view-details">View details</a></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <div class="stat-body">
                        <i class="fas fa-door-open"></i>
                        <div class="stat-text">
                            <h3><?php echo $roomsAvailable; ?></h3>
                            <h5>Rooms Available</h5>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="../../modules/rooms/available.php" class="view-details">View details</a></div>
                </div>
            </div>
        </div>

        <!-- Row 2 -->
        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <div class="stat-card bg-teal">
                    <div class="stat-body">
                        <i class="fas fa-coins"></i>
                        <div class="stat-text">
                            <h3>₱<?php echo number_format($totalIncome,2); ?></h3>
                            <h5>Total Income</h5>
                            <small class="d-block text-white-50">Scope: <?php echo htmlspecialchars($incomeFilterLabel); ?></small>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="../../modules/reports/total_income.php" class="view-details">View details</a></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-card-6" style="background-color:#FF9500;">
                        <div class="stat-body">
                        <i class="fas fa-check-circle"></i>
                        <div class="stat-text">
                            <h3><?php echo $settledCount; ?></h3>
                            <h5>Total Settled</h5>
                        </div>
                    </div>
                   <div class="stat-footer"><a href="../../modules/billing/settled.php" class="view-details">View details</a></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger">
                    <div class="stat-body">
                        <i class="fas fa-clock"></i>
                        <div class="stat-text">
                            <h3><?php echo $pendingCount; ?></h3>
                            <h5>Total Pending Payment</h5>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="../../modules/billing/pending.php" class="view-details">View details</a></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background-color:#AC7E6E !important; color:#fff;">
                    <div class="stat-body">
                        <i class="fas fa-hourglass-half"></i>
                        <div class="stat-text">
                            <h3><?php echo $partialCount; ?></h3>
                            <h5>Total Partial</h5>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="../../modules/billing/partial.php" class="view-details">View details</a></div>
                </div>
            </div>
        </div>

        <!-- Row 3 -->
        <div class="row g-3 mt-4">
            <div class="col-md-8">
                <div class="chart-box">
                    <h5>Monthly Income (<?php echo $currentMonthName . " " . $currentYear; ?>)</h5>
                    <canvas id="billingChart"></canvas>
                </div>
            </div>
           <div class="col-md-4">
    <div class="calendar-box">
        <h5>Calendar</h5>
        <iframe src="<?= BASE_PATH ?>/includes/calendar_widget.php"
                width="100%"
                height="435"
                style="border:none; border-radius:8px;"></iframe>
    </div>
</div>


    </div>
</div>