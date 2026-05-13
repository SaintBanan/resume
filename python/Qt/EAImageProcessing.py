from PyQt5.QtWidgets import QLabel, QSizePolicy
from PyQt5.QtGui import QPainter, QPixmap, QCursor, QImage
from PyQt5.QtCore import QPoint, Qt

import cv2 as cv
import numpy as np
import os, json

class EAImageProcessing(QLabel):
    def __init__(self, image_path):
        super().__init__()

        self.setStyleSheet('background-color: white')
        self.setSizePolicy(QSizePolicy.Ignored, QSizePolicy.Ignored)
        self.setMouseTracking(True)

        self.__image_name = os.path.basename(image_path)
        self.__image = cv.imread(image_path)

        if self.__image is None:
            raise ValueError(f"Не удалось загрузить изображение: {image_path}")
    
        self.__out_image = self.__image.copy()
        self.__grabCut_mask = np.zeros(self.__image.shape[:2], dtype = np.uint8)
        
        self.__is_selecting, self.__is_drawing, self.__is_press, self.__is_mask = False, False, False, False
        self.__start_point, self.__end_point, self.__rect, self.__coordinates = None, None, None, None

        self.base_height, self.base_width = self.__image.shape[:2]

        self.__DRAW_BG = {'color' : [0, 0, 0], 'value' : 0}
        self.__DRAW_FG = {'color' : [255, 255, 255], 'value' : 1}

        self.__View()


    def __View(self):
        img = QImage(self.__out_image.data, self.base_width, self.base_height, QImage.Format_RGB888).rgbSwapped()
        self.__pixmap = QPixmap(img)
        self.repaint()


    def GrayWorld(self):
        self.__out_image = self.__out_image.astype(np.float32)
        self.__out_image /= 255

        b, g, r = cv.split(self.__out_image)
        N = self.__out_image.size
        b_avg, g_avg, r_avg = b.sum() / N, g.sum() / N, r.sum() / N
        avg = (r_avg + g_avg + b_avg) / 3
        b_new, g_new, r_new = b * (avg / b_avg), g * (avg / g_avg), r * (avg / r_avg)

        self.__out_image = cv.merge((b_new, g_new, r_new))
        self.__out_image *= 255
        self.__out_image = self.__out_image.astype(np.uint8)
        self.__View()


    def AdjustGamma(self, gamma):
        inv_gamma = 1.0 / gamma
        table = np.array([((i / 255.0) ** inv_gamma) * 255 for i in np.arange(0, 256)]).astype('uint8')

        self.__out_image = cv.LUT(self.__out_image, table)
        self.__image = self.__out_image.copy()
        self.__View()


    def SelectArea(self):
        self.__is_selecting = True
        self.setCursor(QCursor(Qt.CrossCursor))


    def GrabCut(self):
        bgdmodel = np.zeros((1, 65), np.float64)
        fgdmodel = np.zeros((1, 65), np.float64)

        grabCut_flag = cv.GC_INIT_WITH_MASK if self.__is_mask else cv.GC_INIT_WITH_RECT

        if not self.__is_mask:
            self.__is_mask = True

        cv.grabCut(self.__image, self.__grabCut_mask, self.__rect, bgdmodel, fgdmodel, 1, grabCut_flag)

        mask = np.where((self.__grabCut_mask == 1) + (self.__grabCut_mask == 3), 255, 0).astype('uint8')

        self.__out_image = cv.bitwise_and(self.__image, self.__image, mask = mask)
        self.__View()


    def SelectForeGround(self):
        if self.__is_mask and not self.IsProcessing():
            self.__is_drawing = True
            self.__color = self.__DRAW_FG


    def SelectBackGround(self):
        if self.__is_mask and not self.IsProcessing():
            self.__is_drawing = True
            self.__color = self.__DRAW_BG


    def HistAndBackproj(self):
        if not self.IsProcessing():
            ranges = [0, 180]
            img = self.__out_image

            hist = cv.calcHist([img], [0], None, [180], ranges, accumulate = False)
            cv.normalize(hist, hist, 0, 255, cv.NORM_MINMAX)
            backproj = cv.calcBackProject([img], [0], hist, ranges, 1)

            self.__out_image = cv.bitwise_and(img, img, mask = backproj)
            self.__View()


    def SetMask(self, mask_path, rect, coords):
        self.__rect = rect
        self.__coordinates = coords
        self.__is_mask = True

        mask = cv.imread(mask_path)

        self.__out_image = np.where(mask, self.__image, 0)
        self.__View()


    def SaveResult(self, data_dir, masks_dir, row = 2, col = 3):
        if self.__is_mask and not self.IsProcessing():
            x1, y1 = self.__rect[0], self.__rect[1]
            x2, y2 = x1 + self.__rect[2], y1 + self.__rect[3]

            row_diff = int((y2 - y1) / row)
            col_diff = int((x2 - x1) / col)

            coords = self.__coordinates if self.__coordinates else []
            greenness = []

            for i in range(1, row + 1):
                for j in range(1, col + 1):
                    if not self.__coordinates:
                        _x1 = x1 if j == 1 else x1 + col_diff * (j - 1) + 1
                        _y1 = y1 if i == 1 else y1 + row_diff * (i - 1) + 1

                        _x2 = x1 + col_diff * j if j != col else x2
                        _y2 = y1 + row_diff * i if i != row else y2

                        coords.append([[_x1, _y1], [_x2, _y2]])

                    points = coords[(i - 1) * col + j - 1]
                    gss = self.__Greenness(points[0], points[1])
                    greenness.append(gss)

            image_name = self.__image_name.split('.')[0]

            with open('{0}/{1}.json'.format(data_dir, image_name), 'w') as file:
                json.dump({
                    'coordinates': coords,
                    'greenness': greenness,
                    'rect': self.__rect
                }, file, indent = 2)

            mask = cv.cvtColor(self.__out_image, cv.COLOR_BGR2GRAY)
            mask = np.where(mask, 255, mask)

            cv.imwrite('{0}/{1}.png'.format(masks_dir, image_name), mask)


    """Вычисляет среднюю зеленость (GCC) в выделенной области.
    Возвращает float от 0.0 до 1.0, округленный до 4 знаков."""
    def __Greenness(self, start_point, end_point):
        x1, y1 = start_point
        x2, y2 = end_point
        
        cur_area = self.__out_image[y1:y2, x1:x2].astype(np.float32)
        
        # Разделяем каналы
        b, g, r = cv.split(cur_area)
        mask = (b >= 0.0039) | (g >= 0.0039) | (r >= 0.0039)
        
        # Применяем маску к каналам
        b_masked = b[mask]
        g_masked = g[mask]
        r_masked = r[mask]
        
        if len(b_masked) == 0:
            return 0.0
        
        # Вычисляем GCC только для отфильтрованных пикселей
        denominator = b_masked + g_masked + r_masked
        gss_values = g_masked / denominator
        
        return round(float(np.mean(gss_values)), 4)


    def __CreateCircle(self, point):
        cv.circle(self.__out_image, point, 4, self.__color['color'], -1)
        cv.circle(self.__grabCut_mask, point, 4, self.__color['value'], -1)
        
        self.__View()


    def IsProcessing(self):
        return self.__is_selecting or self.__is_drawing


    def IsGrabCutDone(self):
        return self.__is_mask


    def IsRect(self):
        return True if self.__rect else False


    def mousePressEvent(self, event):
        if event.buttons() & Qt.LeftButton and self.IsProcessing():
            coord = event.pos()
            point = (coord.x(), coord.y())

            if point >= self.__img_start_point and point <= self.__img_end_point:
                if not self.__is_press:
                    self.__is_press = True

                    x1, y1 = point
                    x0, y0 = self.__img_start_point
                    x_coeff, y_coeff = self.__point_diff_coeff

                    self.__start_point = (int((x1 - x0) * x_coeff), int((y1 - y0) * y_coeff))

                    if self.__is_drawing:
                        self.__CreateCircle(self.__start_point)
                else:
                    self.__is_press = False

                    if self.__is_selecting:
                        self.__is_selecting = False

                        x1, y1 = self.__start_point
                        x2, y2 = self.__end_point

                        self.__rect = (min(x1, x2), min(y1, y2), abs(x1 - x2), abs(y1 - y2))
                        self.setCursor(QCursor(Qt.ArrowCursor))
                    else:
                        self.__is_drawing = False



    def mouseMoveEvent(self, event):
        if self.__is_press:
            coord = event.pos()
            x0, y0 = self.__img_start_point
            x_coeff, y_coeff = self.__point_diff_coeff

            self.__end_point = (int((coord.x() - x0) * x_coeff), int((coord.y() - y0) * y_coeff))

            if self.__is_selecting:
                self.__out_image = self.__image.copy()
                cv.rectangle(self.__out_image, self.__start_point, self.__end_point, (0, 255, 0), 2)
                self.__View()
            else:
                self.__CreateCircle(self.__end_point)



    def paintEvent(self, event):
        if not self.__pixmap.isNull():
            scaled_pix = self.__pixmap.scaled(self.size(), Qt.KeepAspectRatio, transformMode = Qt.SmoothTransformation)

            img_width = scaled_pix.width()
            img_height = scaled_pix.height()
            
            x = int((self.width() - img_width) / 2)
            y = int((self.height() - img_height) / 2)

            self.__img_start_point = (x, y)
            self.__img_end_point = (img_width + x - 1, img_height + y - 1)
            self.__point_diff_coeff = (self.base_width / img_width, self.base_height / img_height)

            point = QPoint(x, y)
            painter = QPainter(self)
            painter.drawPixmap(point, scaled_pix)
