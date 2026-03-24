from matplotlib.backends.backend_qt5agg import FigureCanvasQTAgg as FigureCanvas
from matplotlib.figure import Figure
import random, matplotlib, numpy as np

matplotlib.use('Qt5Agg')

class EACanvas(FigureCanvas):
    def __init__(self, data):
        figure = Figure()
        figure.subplots_adjust(left = 0.040, right = 0.990)

        super(EACanvas, self).__init__(figure)

        self.axes = figure.add_subplot(111)
        self.axes.set_title('Зависимость GCC от времени')
        self.__BuildGraph(data)



    def __BuildGraph(self, data):
        data_len = len(data)
        x_data   = np.arange(0, data_len)
        gr_data  = [elem[0] for elem in data]

        for i in range(len(gr_data[0])):
            y_data = []

            for gr_list in gr_data:
                y_data.append(gr_list[i])
            
            self.axes.plot(x_data, y_data)
        
        ###  form x  ###

        data_step = int(data_len / 6)
        x_ticks   = np.arange(0, data_len, data_step if data_step else 1)
        x_labels  = []

        for x_tick in x_ticks:
            x_labels.append(data[x_tick][1])

        self.axes.set_xticks(x_ticks)
        self.axes.set_xticklabels(x_labels)

        ###  form y  ###

        y_ticks  = np.arange(0.2, 0.6, 0.1)
        y_ticks  = [round(elem, 1) for elem in y_ticks]
        y_labels = []

        for y_tick in y_ticks:
            y_labels.append(str(y_tick))

        self.axes.set_yticks(y_ticks)
        self.axes.set_yticklabels(y_labels)
