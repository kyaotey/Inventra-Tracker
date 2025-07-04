// ... existing code ...
                                <!-- Timeline -->
                                <div class="timeline mt-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fas fa-history me-2"></i><?= $category_text ?> Timeline
                                    </h5>
                                    <!-- Timeline Steps -->
                                    <div class="timeline-item">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary"><i class="fas fa-flag"></i></span>
                                            <span class="fw-bold">Reported</span>
                                        </div>
                                        <div class="text-muted ms-4">
                                            <?= date('F j, Y \a\t g:i A', strtotime($report['created_at'])) ?>
                                        </div>
                                    </div>
                                    <?php if ($report['status'] !== 'returned'): ?>
                                        <div class="timeline-item">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i></span>
                                                <span class="fw-bold">In Progress</span>
                                            </div>
                                            <div class="text-muted ms-4">Still waiting to be found or claimed.</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($report['status'] === 'returned'): ?>
                                        <div class="timeline-item">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-success"><i class="fas fa-check-circle"></i></span>
                                                <span class="fw-bold">
                                                    <?php if ($report['category'] === 'person') { echo 'Person Reunited'; } else { echo $category_text . ' Returned'; } ?>
                                                </span>
                                            </div>
                                            <div class="text-muted ms-4">Reunited with their family, friends, or caregivers.</div>
                                            <div class="text-muted ms-4 small mt-1"><i class="fas fa-calendar-check me-1"></i>Returned on <?= date('F j, Y', strtotime($report['updated_at'] ?? $report['created_at'])) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
// ... existing code ...
                                <!-- Comments Section -->
                                <div class="mt-5">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fas fa-comments me-2"></i>Comments
                                    </h5>
                                    <?php
                                    // ... existing code ...
                                    ?>
                                    <div class="mb-3">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                        <form method="post" class="d-flex align-items-start gap-2">
                                            <textarea name="comment" class="form-control rounded-3 shadow-sm" rows="2" placeholder="Add a comment..." required></textarea>
                                            <button type="submit" name="add_comment" class="btn btn-primary rounded-3 shadow-sm"><i class="fas fa-paper-plane"></i></button>
                                        </form>
                                        <?php else: ?>
                                        <div class="alert alert-info py-2">Please <a href="login.php">login</a> to comment.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($comments->num_rows > 0): ?>
                                            <?php while ($c = $comments->fetch_assoc()): ?>
                                                <div class="border rounded-3 p-3 mb-2 bg-light position-relative shadow-sm">
                                                    <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                                                        <i class="fas fa-user-circle me-1 text-primary"></i><?= htmlspecialchars($c['name']) ?> <span class="text-muted small ms-2"><i class="fas fa-clock me-1"></i><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></span>
                                                    </div>
                                                    <div class="text-muted mb-1" style="font-size:0.98rem;">"<?= htmlspecialchars($c['comment']) ?>"</div>
                                                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                                        <form method="post" class="position-absolute top-0 end-0 m-2">
                                                            <button type="submit" name="delete_comment" value="<?= $c['id'] ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="text-muted">No comments yet. Be the first to comment!</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
// ... existing code ...