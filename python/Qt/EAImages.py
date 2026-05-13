from PyQt5.QtWidgets import QLabel, QSizePolicy
from PyQt5.QtGui import QPainter, QPixmap
from PyQt5.QtCore import QPoint, Qt
import os

class EAImages(QLabel):
    def __init__(self, images):
        super().__init__()
        
        self.setStyleSheet('background-color: white')
        self.setSizePolicy(QSizePolicy.Ignored, QSizePolicy.Ignored)
        
        self.__images_path = [image[1] for image in images]
        self.__images_date = [image[0] for image in images]
        self.__images_len = len(images)
        self.__pixmap = QPixmap('')
        self.__position = 0


    def View(self):
        self.__pixmap = QPixmap(self.GetCurrentImagePath())
        self.repaint()


    def Next(self):
        if self.IsNext():
            self.__position += 1
            self.View()


    def Prev(self):
        if self.IsPrev():
            self.__position -= 1
            self.View()


    def IsNext(self):
        return self.__position < self.__images_len - 1


    def IsPrev(self):
        return self.__position != 0


    def IsImages(self):
        return self.__images_len != 0


    def SelectImage(self, position):
        if position >= 0 and position < self.__images_len and position != self.__position:
            self.__position = position
            self.View()


    def GetImagesPath(self):
        return self.__images_path


    def GetCurrentImagePath(self):
        return self.__images_path[self.__position]


    def GetCurrentImageName(self):
        return os.path.basename(self.GetCurrentImagePath())


    def GetCurrentPosition(self):
        return self.__position

    
    def GetImagesCount(self):
        return self.__images_len


    def GetImageDate(self, image_name):
        for i, path in enumerate(self.__images_path):
            image = os.path.basename(path)

            if image_name == image.split('.')[0]:
                return self.__images_date[i]

        return None


    def AddImage(self, image_path, image_date):
        position = self.__images_len
        self.__images_len += 1
        self.__images_path.append(image_path)
        self.__images_date.append(image_date)
        self.__position = position

        return position
    
    
    def paintEvent(self, event):
        if not self.__pixmap.isNull():
            scaled_pix = self.__pixmap.scaled(self.size(), Qt.KeepAspectRatio, transformMode = Qt.SmoothTransformation)
            
            x = (self.width() - scaled_pix.width()) / 2
            y = (self.height() - scaled_pix.height()) / 2

            point = QPoint(x, y)
            painter = QPainter(self)
            painter.drawPixmap(point, scaled_pix)
